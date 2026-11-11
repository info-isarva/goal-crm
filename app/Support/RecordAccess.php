<?php

namespace App\Support;

use App\Models\CallLog;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Record-level visibility rules, matching the filters used by the lead/deal list pages:
 *  - Super Admin / Admin: every record.
 *  - Manager: own records, records of employees assigned to them, and unassigned records.
 *  - Employee: records they created or own.
 */
class RecordAccess
{
    private const SHARED_INBOX_EMAIL = 'marketing@isarvait.com';

    /** @var array<int, array<int, int>> */
    private static array $teamCache = [];

    public static function isAdmin(?User $user): bool
    {
        return $user !== null && in_array((int) ($user->crm_role_type ?? -1), [0, 1], true);
    }

    public static function isManager(?User $user): bool
    {
        return $user !== null && (int) ($user->crm_role_type ?? -1) === 2;
    }

    /** @return array<int, int> */
    public static function teamIds(User $manager): array
    {
        return self::$teamCache[$manager->id] ??= User::where('assign_manager', $manager->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Whether $user may see a record created by $createdBy and owned by $ownerId.
     */
    public static function canAccessOwned(?User $user, $createdBy, $ownerId): bool
    {
        if (! $user) {
            return false;
        }
        if (self::isAdmin($user)) {
            return true;
        }

        $me = (int) $user->id;
        $created = self::idOrNull($createdBy);
        $owner = self::idOrNull($ownerId);

        if ($created === $me || $owner === $me) {
            return true;
        }

        if (self::isManager($user)) {
            $team = self::teamIds($user);
            return $created === null
                || in_array($created, $team, true)
                || ($owner !== null && in_array($owner, $team, true));
        }

        if (strcasecmp((string) $user->email, self::SHARED_INBOX_EMAIL) === 0) {
            return $created === null || $owner === null;
        }

        return false;
    }

    public static function canAccessLead(?User $user, ?Lead $lead): bool
    {
        return $lead !== null && self::canAccessOwned($user, $lead->created_by, $lead->user_owner_id);
    }

    public static function canAccessDeal(?User $user, ?Deal $deal): bool
    {
        return $deal !== null && self::canAccessOwned($user, $deal->created_by, $deal->user_owner_id);
    }

    /**
     * Access check for an item attached to a lead or deal through related_type / related_id.
     */
    public static function canAccessRelated(?User $user, ?string $relatedType, $relatedId): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        return match (strtolower((string) $relatedType)) {
            'lead' => self::canAccessLead($user, Lead::find($relatedId)),
            'deal' => self::canAccessDeal($user, Deal::find($relatedId)),
            'call_log' => ($log = CallLog::find($relatedId)) !== null
                && self::canAccessOwned($user, $log->created_by, null),
            // Contacts are shared master data in this CRM
            'contact', 'person' => $user !== null && Person::whereKey($relatedId)->exists(),
            default => false,
        };
    }

    /**
     * Activities (tasks, notes, calls, meetings, files): allowed when the user can access the
     * parent lead/deal, or is personally involved in the activity (creator, owner, assignee).
     */
    public static function canAccessActivity(?User $user, Model $activity): bool
    {
        if (! $user) {
            return false;
        }
        if (self::isAdmin($user)) {
            return true;
        }

        $me = (int) $user->id;
        foreach (['created_by', 'user_owner_id', 'user_assigned_id', 'uploaded_by'] as $column) {
            if (self::idOrNull($activity->getAttribute($column)) === $me) {
                return true;
            }
        }

        if (self::isManager($user)) {
            $team = self::teamIds($user);
            foreach (['created_by', 'user_owner_id', 'user_assigned_id'] as $column) {
                $id = self::idOrNull($activity->getAttribute($column));
                if ($id !== null && in_array($id, $team, true)) {
                    return true;
                }
            }
        }

        return self::canAccessRelated($user, $activity->getAttribute('related_type'), $activity->getAttribute('related_id'));
    }

    public static function authorizeLead(?Lead $lead): void
    {
        abort_unless(self::canAccessLead(auth()->user(), $lead), 403, 'You do not have access to this lead.');
    }

    public static function authorizeDeal(?Deal $deal): void
    {
        abort_unless(self::canAccessDeal(auth()->user(), $deal), 403, 'You do not have access to this deal.');
    }

    public static function authorizeRelated(?string $relatedType, $relatedId): void
    {
        abort_unless(self::canAccessRelated(auth()->user(), $relatedType, $relatedId), 403, 'You do not have access to this record.');
    }

    public static function authorizeActivity(Model $activity): void
    {
        abort_unless(self::canAccessActivity(auth()->user(), $activity), 403, 'You do not have access to this record.');
    }

    private static function idOrNull($value): ?int
    {
        if ($value === null || $value === '' || (int) $value === 0) {
            return null;
        }

        return (int) $value;
    }
}
