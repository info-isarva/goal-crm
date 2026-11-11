<?php

namespace App\Http\Controllers;

use App\Exports\ModuleDataExport;
use App\Helpers\MoneyFormatter;
use App\Models\Call;
use App\Models\CallLog;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Meeting;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quotation;
use App\Models\StageHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class DataExportController extends Controller
{
    public const MAX_RECORDS = 200000;

    protected function ensureCanExport(): void
    {
        $user = auth()->user();
        if (!$user || !in_array((int) ($user->crm_role_type ?? -1), [0, 1], true)) {
            abort(403, 'Only Admin can export CRM data.');
        }
    }

    /**
     * Module registry for Zoho-style export.
     */
    public static function modules(): array
    {
        return [
            'leads' => [
                'label' => 'Leads',
                'description' => 'All CRM leads',
                'views' => [
                    ['value' => 'all', 'label' => 'All Leads'],
                    ['value' => 'open', 'label' => 'Open Leads'],
                    ['value' => 'converted', 'label' => 'Converted Leads'],
                    ['value' => 'junk', 'label' => 'Junk Leads'],
                    ['value' => 'my', 'label' => 'My Leads'],
                ],
            ],
            'deals' => [
                'label' => 'Deals',
                'description' => 'All CRM deals',
                'views' => [
                    ['value' => 'all', 'label' => 'All Deals'],
                    ['value' => 'open', 'label' => 'Open Deals'],
                    ['value' => 'closed_won', 'label' => 'Closed Won'],
                    ['value' => 'closed_lost', 'label' => 'Closed Lost'],
                    ['value' => 'my', 'label' => 'My Deals'],
                ],
            ],
            'deals_stage_history' => [
                'label' => 'Deals - Stage History',
                'description' => 'Deal stage change history',
                'views' => [
                    ['value' => 'all', 'label' => 'All Stage History'],
                ],
            ],
            'contacts' => [
                'label' => 'Contacts',
                'description' => 'Contact persons',
                'views' => [
                    ['value' => 'all', 'label' => 'All Contacts'],
                    ['value' => 'my', 'label' => 'My Contacts'],
                ],
            ],
            'companies' => [
                'label' => 'Companies',
                'description' => 'Organizations / companies',
                'views' => [
                    ['value' => 'all', 'label' => 'All Companies'],
                    ['value' => 'my', 'label' => 'My Companies'],
                ],
            ],
            'customers' => [
                'label' => 'Company Owners',
                'description' => 'Company owner / customer records',
                'views' => [
                    ['value' => 'all', 'label' => 'All Company Owners'],
                ],
            ],
            'call_logs' => [
                'label' => 'Call Logs',
                'description' => 'Outbound / inbound call logs',
                'views' => [
                    ['value' => 'all', 'label' => 'All Call Logs'],
                    ['value' => 'my', 'label' => 'My Call Logs'],
                ],
            ],
            'calls' => [
                'label' => 'Calls',
                'description' => 'Scheduled / logged calls',
                'views' => [
                    ['value' => 'all', 'label' => 'All Calls'],
                    ['value' => 'my', 'label' => 'My Calls'],
                ],
            ],
            'meetings' => [
                'label' => 'Meetings',
                'description' => 'Meeting activities',
                'views' => [
                    ['value' => 'all', 'label' => 'All Meetings'],
                    ['value' => 'my', 'label' => 'My Meetings'],
                ],
            ],
            'tasks' => [
                'label' => 'Tasks',
                'description' => 'Task activities',
                'views' => [
                    ['value' => 'all', 'label' => 'All Tasks'],
                    ['value' => 'open', 'label' => 'Open Tasks'],
                    ['value' => 'completed', 'label' => 'Completed Tasks'],
                    ['value' => 'my', 'label' => 'My Tasks'],
                ],
            ],
            'notes' => [
                'label' => 'Notes',
                'description' => 'Notes on leads / deals',
                'views' => [
                    ['value' => 'all', 'label' => 'All Notes'],
                ],
            ],
            'products' => [
                'label' => 'Products',
                'description' => 'Product catalog',
                'views' => [
                    ['value' => 'all', 'label' => 'All Products'],
                ],
            ],
            'product_categories' => [
                'label' => 'Product Categories',
                'description' => 'Product / service categories',
                'views' => [
                    ['value' => 'all', 'label' => 'All Categories'],
                ],
            ],
            'quotations' => [
                'label' => 'Quotations',
                'description' => 'Quotation headers',
                'views' => [
                    ['value' => 'all', 'label' => 'All Quotations'],
                ],
            ],
            'industries' => [
                'label' => 'Industries',
                'description' => 'Industry master list',
                'views' => [
                    ['value' => 'all', 'label' => 'All Industries'],
                ],
            ],
            'lead_sources' => [
                'label' => 'Lead Sources',
                'description' => 'Lead source master list',
                'views' => [
                    ['value' => 'all', 'label' => 'All Lead Sources'],
                ],
            ],
            'users' => [
                'label' => 'Users',
                'description' => 'CRM users (no passwords)',
                'views' => [
                    ['value' => 'all', 'label' => 'All Users'],
                    ['value' => 'active', 'label' => 'Active Users'],
                ],
            ],
        ];
    }

    public static function getModuleFieldsMap(): array
    {
        return [
            'leads' => ['ID', 'Title', 'Company', 'Contact', 'Status', 'Priority', 'Amount', 'Lead Source', 'Referral Name', 'Industry', 'Owner', 'Created At', 'Updated At', 'Converted At'],
            'deals' => ['ID', 'Title', 'Company', 'Contact', 'Stage', 'Status', 'Priority', 'Amount', 'Probability', 'Lead Source', 'Referral Name', 'Industry', 'Owner', 'Close Date', 'Created At', 'Updated At'],
            'deals_stage_history' => ['ID', 'Deal ID', 'Deal Title', 'Stage', 'Amount', 'Probability', 'Close Date', 'Modified By', 'Modified Time'],
            'contacts' => ['ID', 'First Name', 'Last Name', 'Email', 'Mobile', 'Phone', 'Designation', 'Company', 'Owner', 'Created At', 'Updated At'],
            'companies' => ['ID', 'Name', 'Industry', 'Address', 'Country', 'State', 'City', 'Phone', 'Website', 'Owner', 'Created At', 'Updated At'],
            'customers' => ['ID', 'Name', 'Company', 'Email', 'Phone', 'Owner', 'Created At', 'Updated At'],
            'call_logs' => ['ID', 'Name', 'Company', 'Mobile', 'Email', 'Type', 'Call Status', 'Lead Status', 'Estimated Budget', 'Source', 'Next Follow Up', 'Created By', 'Created At'],
            'calls' => ['ID', 'Subject', 'Related Type', 'Related ID', 'Location', 'Start At', 'End At', 'Owner', 'Created At'],
            'meetings' => ['ID', 'Subject', 'Related Type', 'Related ID', 'Venue', 'Start At', 'End At', 'Owner', 'Created At'],
            'tasks' => ['ID', 'Task', 'Status', 'Priority', 'Related Type', 'Related ID', 'Due At', 'Owner', 'Created At', 'Updated At'],
            'notes' => ['ID', 'Related Type', 'Related ID', 'Content', 'Created By', 'Created At', 'Updated At'],
            'products' => ['ID', 'Product Name', 'Product Code', 'Type', 'Unit Price', 'Status', 'Category', 'Owner', 'Created At', 'Updated At'],
            'product_categories' => ['ID', 'Category Name', 'Status', 'Created At', 'Updated At'],
            'quotations' => ['ID', 'Quote No', 'Subject', 'Status', 'Deal ID', 'Organization', 'Grand Total', 'Valid Until', 'Created At', 'Updated At'],
            'industries' => ['ID', 'Name', 'Description', 'Created At', 'Updated At'],
            'lead_sources' => ['ID', 'Name', 'Created At', 'Updated At'],
            'users' => ['ID', 'Name', 'Email', 'Role Type', 'Sales Target', 'Status', 'Created At'],
        ];
    }

    public function index()
    {
        $this->ensureCanExport();

        $modules = collect(self::modules())
            ->map(fn ($meta, $key) => array_merge(['key' => $key], $meta))
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $users = User::select('id', 'name')->orderBy('name')->get();
        $moduleFields = self::getModuleFieldsMap();

        return view('settings.data_export', [
            'modules' => $modules,
            'maxRecords' => self::MAX_RECORDS,
            'users' => $users,
            'moduleFields' => $moduleFields,
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureCanExport();

        $moduleKeys = implode(',', array_keys(self::modules()));
        $validated = $request->validate([
            'module' => 'required|string|in:' . $moduleKeys,
            'filter_by' => 'nullable|in:custom_view,criteria',
            'custom_view' => 'nullable|string|max:50',
            'criteria_text' => 'nullable|string|max:255',
            'criteria_rows' => 'nullable|array',
            'criteria_rows.*.field' => 'nullable|string',
            'criteria_rows.*.operator' => 'nullable|string',
            'criteria_rows.*.value' => 'nullable|string',
            'fields_mode' => 'nullable|in:all,choose',
            'selected_fields' => 'nullable|array',
            'selected_fields.*' => 'nullable|string',
            'exclude_fields' => 'nullable|boolean',
            'format' => 'required|in:xlsx,csv',
            'charset' => 'nullable|string|in:UTF-8,UTF-16,ISO-8859-1,ISO-8859-2,ISO-8859-8,ISO-8859-9,ISO-8859-11,GB2312,BIG5,EUC-JP,Shift_JIS,KOI8-R,Windows-1252',
            'set_password' => 'nullable|boolean',
            'export_password' => 'nullable|string|min:4|max:64|confirmed',
        ]);

        if ($request->boolean('set_password') && empty($validated['export_password'])) {
            return back()
                ->withInput()
                ->withErrors(['export_password' => 'Please enter and confirm a password.']);
        }

        $module = $validated['module'];
        $format = $validated['format'];
        $meta = self::modules()[$module];
        $filterBy = $validated['filter_by'] ?? 'custom_view';
        $view = $filterBy === 'criteria' ? 'all' : ($validated['custom_view'] ?: 'all');
        $criteria = $filterBy === 'criteria' ? trim((string) ($validated['criteria_text'] ?? '')) : '';
        $excludeIds = $request->boolean('exclude_fields');
        $charset = $validated['charset'] ?? 'UTF-8';

        $allowedViews = collect($meta['views'] ?? [['value' => 'all']])->pluck('value')->all();
        if (!in_array($view, $allowedViews, true)) {
            $view = 'all';
        }

        [$headings, $rows] = $this->buildModuleRows($module, $view, $criteria);

        if ($filterBy === 'criteria' && !empty($validated['criteria_rows'])) {
            $rows = $this->applyCriteriaRules($headings, $rows, $validated['criteria_rows']);
        }

        if (($validated['fields_mode'] ?? 'all') === 'choose' && !empty($validated['selected_fields'])) {
            [$headings, $rows] = $this->filterSelectedColumns($headings, $rows, $validated['selected_fields']);
        }

        if ($excludeIds) {
            [$headings, $rows] = $this->stripIdColumns($headings, $rows);
        }

        $export = new ModuleDataExport($rows, $headings, $meta['label']);
        $stamp = now()->format('Ymd_His');
        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $meta['label']);
        $fileName = 'CRM_' . $safeName . '_' . $stamp . '.' . $format;
        $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        if ($request->boolean('set_password') && !empty($validated['export_password'])) {
            return $this->downloadAsPasswordZip(
                $export,
                $fileName,
                $writerType,
                $validated['export_password'],
                $charset
            );
        }

        $download = Excel::download($export, $fileName, $writerType);
        if ($format === 'csv' && method_exists($download, 'headers')) {
            // Charset hint for CSV consumers
            $download->headers->set('Content-Type', 'text/csv; charset=' . $charset);
        }

        return $download;
    }

    protected function downloadAsPasswordZip($export, string $fileName, string $writerType, string $password, string $charset)
    {
        $relTempName = 'temp_exports/' . uniqid('export_', true) . '_' . $fileName;
        Excel::store($export, $relTempName, 'local', $writerType);

        $stored = Storage::disk('local')->path($relTempName);
        if (!file_exists($stored)) {
            return redirect()->back()->with('error', 'Export temporary file could not be created.');
        }

        $zipName = pathinfo($fileName, PATHINFO_FILENAME) . '.zip';
        $relZipName = 'temp_exports/' . uniqid('zip_', true) . '.zip';
        $zipPath = Storage::disk('local')->path($relZipName);

        $zipDir = dirname($zipPath);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        if (!class_exists(\ZipArchive::class)) {
            @unlink($stored);
            return redirect()->back()->with('error', 'PHP Zip extension is not loaded in the web server process. Please restart your web server (Apache / php artisan serve).');
        }

        if (!defined('ZipArchive::EM_AES_256') || !method_exists(\ZipArchive::class, 'setEncryptionName')) {
            @unlink($stored);
            return redirect()->back()->with('error', 'Password-protected export is not supported by the PHP Zip extension on this server.');
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                @unlink($stored);
                return redirect()->back()->with('error', 'Unable to create protected export file.');
            }

            // Password protection
            $zip->setPassword($password);
            $zip->addFile($stored, $fileName);
            $encrypted = $zip->setEncryptionName($fileName, \ZipArchive::EM_AES_256, $password);

            if (!$zip->close() || !$encrypted) {
                throw new \RuntimeException('Zip encryption failed.');
            }
            @unlink($stored);

            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($stored);
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            Log::error('Protected export failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Export failed. Please try again.');
        }
    }

    protected function applyCriteriaRules(array $headings, array $rows, array $criteriaRows): array
    {
        if (empty($criteriaRows)) {
            return $rows;
        }

        $validRules = [];
        foreach ($criteriaRows as $rule) {
            $field = trim((string)($rule['field'] ?? ''));
            $op = trim((string)($rule['operator'] ?? 'is'));
            $val = trim((string)($rule['value'] ?? ''));
            if ($field === '') {
                continue;
            }
            $validRules[] = [
                'field' => $field,
                'operator' => $op,
                'value' => $val,
            ];
        }

        if (empty($validRules)) {
            return $rows;
        }

        $normalize = function ($s) {
            return strtolower(trim(str_replace('*', '', (string)$s)));
        };

        $filteredRows = [];
        foreach ($rows as $row) {
            $passAll = true;
            foreach ($validRules as $rule) {
                $targetNorm = $normalize($rule['field']);
                $colIndex = -1;
                foreach ($headings as $i => $h) {
                    if ($normalize($h) === $targetNorm) {
                        $colIndex = $i;
                        break;
                    }
                }

                if ($colIndex === -1) {
                    continue;
                }

                $cellVal = (string)($row[$colIndex] ?? '');
                $op = $rule['operator'];
                $val = $rule['value'];

                $matched = match ($op) {
                    'is', 'equals' => strtolower($cellVal) === strtolower($val),
                    'is_not', 'not_equals' => strtolower($cellVal) !== strtolower($val),
                    'contains' => stripos($cellVal, $val) !== false,
                    'does_not_contain' => stripos($cellVal, $val) === false,
                    'starts_with' => str_starts_with(strtolower($cellVal), strtolower($val)),
                    'ends_with' => str_ends_with(strtolower($cellVal), strtolower($val)),
                    'is_empty' => trim($cellVal) === '',
                    'is_not_empty' => trim($cellVal) !== '',
                    default => true,
                };

                if (!$matched) {
                    $passAll = false;
                    break;
                }
            }

            if ($passAll) {
                $filteredRows[] = $row;
            }
        }

        return $filteredRows;
    }

    protected function filterSelectedColumns(array $headings, array $rows, array $selectedFields): array
    {
        if (empty($selectedFields)) {
            return [$headings, $rows];
        }

        $normalize = function ($str) {
            $s = strtolower(trim(str_replace(['*', '_', '-'], ' ', (string)$str)));
            return match ($s) {
                'id', 'record id' => 'id',
                'quote no', 'quotation number', 'quote number' => 'quote_no',
                'company', 'organization' => 'company',
                'total amount', 'grand total', 'amount' => 'amount',
                'task', 'subject' => 'subject',
                'name', 'product name', 'category name' => 'name',
                'code', 'product code' => 'code',
                'price', 'unit price' => 'price',
                'author', 'created by', 'modified by' => 'creator',
                'role', 'role type' => 'role',
                default => preg_replace('/\s+/', ' ', $s),
            };
        };

        $selectedNormalized = array_map($normalize, $selectedFields);
        $keepIndexes = [];

        foreach ($headings as $i => $heading) {
            $norm = $normalize($heading);
            if (in_array($norm, $selectedNormalized, true) || in_array($heading, $selectedFields, true)) {
                $keepIndexes[] = $i;
            }
        }

        if (empty($keepIndexes)) {
            return [$headings, $rows];
        }

        $newHeadings = array_values(array_map(fn ($i) => $headings[$i], $keepIndexes));
        $newRows = [];
        foreach ($rows as $row) {
            $newRows[] = array_values(array_map(fn ($i) => $row[$i] ?? null, $keepIndexes));
        }

        return [$newHeadings, $newRows];
    }

    protected function stripIdColumns(array $headings, array $rows): array
    {
        $keepIndexes = [];
        foreach ($headings as $i => $heading) {
            $h = strtolower(trim((string) $heading));
            if ($h === 'id' || str_ends_with($h, ' id')) {
                continue;
            }
            $keepIndexes[] = $i;
        }

        $newHeadings = array_values(array_map(fn ($i) => $headings[$i], $keepIndexes));
        $newRows = [];
        foreach ($rows as $row) {
            $newRows[] = array_values(array_map(fn ($i) => $row[$i] ?? null, $keepIndexes));
        }

        return [$newHeadings, $newRows];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array>}
     */
    protected function buildModuleRows(string $module, string $view = 'all', string $criteria = ''): array
    {
        return match ($module) {
            'leads' => $this->exportLeads($view, $criteria),
            'deals' => $this->exportDeals($view, $criteria),
            'deals_stage_history' => $this->exportDealStageHistory($view, $criteria),
            'contacts' => $this->exportContacts($view, $criteria),
            'companies' => $this->exportCompanies($view, $criteria),
            'customers' => $this->exportCustomers($view, $criteria),
            'call_logs' => $this->exportCallLogs($view, $criteria),
            'calls' => $this->exportCalls($view, $criteria),
            'meetings' => $this->exportMeetings($view, $criteria),
            'tasks' => $this->exportTasks($view, $criteria),
            'notes' => $this->exportNotes($view, $criteria),
            'products' => $this->exportProducts($view, $criteria),
            'product_categories' => $this->exportProductCategories($view, $criteria),
            'quotations' => $this->exportQuotations($view, $criteria),
            'industries' => $this->exportIndustries($view, $criteria),
            'lead_sources' => $this->exportLeadSources($view, $criteria),
            'users' => $this->exportUsers($view, $criteria),
            default => [[], []],
        };
    }

    protected function exportLeads(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Title', 'Company', 'Contact', 'Status', 'Priority', 'Amount',
            'Lead Source', 'Referral Name', 'Industry', 'Owner', 'Created At', 'Updated At', 'Converted At',
        ];

        $query = Lead::query()->with(['organization', 'person', 'owner', 'leadSource', 'industry']);

        if ($view === 'open') {
            $query->whereNull('converted_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
                });
        } elseif ($view === 'converted') {
            $query->whereNotNull('converted_at');
        } elseif ($view === 'junk') {
            $query->where('status', 'Junk Lead');
        } elseif ($view === 'my') {
            $query->where('user_owner_id', auth()->id());
        } else {
            // All leads: exclude junk by default (same as list)
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            });
        }

        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhere('referral_name', 'like', $term)
                    ->orWhereHas('organization', fn ($oq) => $oq->where('name', 'like', $term))
                    ->orWhereHas('person', function ($pq) use ($term) {
                        $pq->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term);
                    });
            });
        }

        $rows = $query->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Lead $lead) {
                $contact = $lead->person
                    ? trim(($lead->person->first_name ?? '') . ' ' . ($lead->person->last_name ?? ''))
                    : '';
                return [
                    $lead->id,
                    $lead->title,
                    optional($lead->organization)->name,
                    $contact !== '' ? $contact : null,
                    $lead->status,
                    $lead->label,
                    is_numeric($lead->amount) ? MoneyFormatter::formatIndian($lead->amount) : $lead->amount,
                    optional($lead->leadSource)->name,
                    $lead->referral_name,
                    optional($lead->industry)->name,
                    optional($lead->owner)->name,
                    optional($lead->created_at)?->format('Y-m-d H:i:s'),
                    optional($lead->updated_at)?->format('Y-m-d H:i:s'),
                    optional($lead->converted_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportDeals(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Title', 'Company', 'Contact', 'Stage', 'Status', 'Priority', 'Amount',
            'Probability', 'Lead Source', 'Referral Name', 'Industry', 'Owner',
            'Close Date', 'Created At', 'Updated At',
        ];

        $query = Deal::query()->with(['organization', 'person', 'owner', 'dealSource', 'industry', 'stage']);

        if ($view === 'open') {
            $query->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(status) not in (?, ?)', ['closed won', 'closed lost']);
            });
            if (method_exists(\App\Models\Stage::class, 'closedWonId')) {
                $wonId = \App\Models\Stage::closedWonId();
                if ($wonId) {
                    $query->where(function ($q) use ($wonId) {
                        $q->whereNull('stage_id')->orWhere('stage_id', '!=', $wonId);
                    });
                }
            }
        } elseif ($view === 'closed_won') {
            $query->where(function ($q) {
                $q->where('stage_id', \App\Models\Stage::closedWonId())
                    ->orWhereRaw('LOWER(status) = ?', ['closed won']);
            });
        } elseif ($view === 'closed_lost') {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['closed lost'])
                    ->orWhereRaw('LOWER(status) = ?', ['lost']);
            });
        } elseif ($view === 'my') {
            $query->where('user_owner_id', auth()->id());
        }

        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhereHas('organization', fn ($oq) => $oq->where('name', 'like', $term));
            });
        }

        $rows = $query->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Deal $deal) {
                $contact = $deal->person
                    ? trim(($deal->person->first_name ?? '') . ' ' . ($deal->person->last_name ?? ''))
                    : '';
                return [
                    $deal->id,
                    $deal->title,
                    optional($deal->organization)->name,
                    $contact !== '' ? $contact : null,
                    $deal->stage_name ?? optional($deal->stage)->name,
                    $deal->status,
                    $deal->label,
                    is_numeric($deal->amount) ? MoneyFormatter::formatIndian($deal->amount) : $deal->amount,
                    $deal->probability,
                    optional($deal->dealSource)->name,
                    $deal->referral_name,
                    optional($deal->industry)->name,
                    optional($deal->owner)->name,
                    $deal->close_date,
                    optional($deal->created_at)?->format('Y-m-d H:i:s'),
                    optional($deal->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportDealStageHistory(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Deal ID', 'Deal Title', 'Stage', 'Amount', 'Probability',
            'Close Date', 'Modified By', 'Modified Time',
        ];

        if (!Schema::hasTable('stage_history')) {
            return [$headings, []];
        }

        $query = StageHistory::query()->with(['deal', 'user']);
        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('stage_name', 'like', $term)
                    ->orWhereHas('deal', fn ($dq) => $dq->where('title', 'like', $term));
            });
        }

        $rows = $query->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (StageHistory $row) {
                return [
                    $row->id,
                    $row->deal_id,
                    optional($row->deal)->title,
                    $row->stage_name,
                    is_numeric($row->amount) ? MoneyFormatter::formatIndian($row->amount) : $row->amount,
                    $row->probability,
                    $row->close_date,
                    optional($row->user)->name,
                    $row->modified_time,
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportContacts(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'First Name', 'Last Name', 'Email', 'Mobile', 'Phone', 'Designation',
            'Company', 'Owner', 'Created At', 'Updated At',
        ];

        $query = Person::query()->with(['owner', 'organization']);
        if ($view === 'my') {
            $query->where('user_owner_id', auth()->id());
        }
        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('mobile', 'like', $term);
            });
        }

        $rows = $query->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Person $person) {
                return [
                    $person->id,
                    $person->first_name,
                    $person->last_name,
                    $person->email,
                    $person->mobile,
                    $person->phone,
                    $person->job_title,
                    optional($person->organization)->name,
                    optional($person->owner)->name,
                    optional($person->created_at)?->format('Y-m-d H:i:s'),
                    optional($person->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportCompanies(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Name', 'Industry', 'Address', 'Country', 'State', 'City',
            'Phone', 'Website', 'Owner', 'Created At', 'Updated At',
        ];

        $rows = Organization::query()
            ->with(['owner', 'industry'])
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Organization $org) {
                return [
                    $org->id,
                    $org->name,
                    optional($org->industry)->name,
                    $org->address,
                    $org->country,
                    $org->state,
                    $org->city,
                    $org->phone,
                    $org->website,
                    optional($org->owner)->name,
                    optional($org->created_at)?->format('Y-m-d H:i:s'),
                    optional($org->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportCustomers(string $view = 'all', string $criteria = ''): array
    {
        $headings = ['ID', 'Name', 'Company', 'Email', 'Phone', 'Owner', 'Created At', 'Updated At'];

        $rows = Customer::query()
            ->with(['organization', 'owner'])
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Customer $customer) {
                return [
                    $customer->id,
                    $customer->name,
                    optional($customer->organization)->name,
                    $customer->email,
                    $customer->phone,
                    optional($customer->owner)->name,
                    optional($customer->created_at)?->format('Y-m-d H:i:s'),
                    optional($customer->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportCallLogs(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Name', 'Company', 'Mobile', 'Email', 'Type', 'Call Status', 'Lead Status',
            'Estimated Budget', 'Source', 'Next Follow Up', 'Created By', 'Created At',
        ];

        $rows = CallLog::query()
            ->with('creator')
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (CallLog $log) {
                return [
                    $log->id,
                    $log->name,
                    $log->company_name,
                    $log->mobile_number,
                    $log->email,
                    $log->type,
                    $log->call_status,
                    $log->lead_status,
                    $log->estimated_budget,
                    $log->source,
                    $log->next_follow_up_date,
                    optional($log->creator)->name,
                    optional($log->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportCalls(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Subject', 'Related Type', 'Related ID', 'Location',
            'Start At', 'End At', 'Owner', 'Created At',
        ];

        $ownerNames = User::pluck('name', 'id');
        $rows = Call::query()
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Call $call) use ($ownerNames) {
                return [
                    $call->id,
                    $call->name,
                    $call->related_type,
                    $call->related_id,
                    $call->location,
                    $call->start_at,
                    $call->finish_at,
                    $ownerNames[$call->user_owner_id] ?? ($ownerNames[$call->created_by] ?? null),
                    optional($call->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportMeetings(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Subject', 'Related Type', 'Related ID', 'Venue',
            'Start At', 'End At', 'Owner', 'Created At',
        ];

        $ownerNames = User::pluck('name', 'id');
        $rows = Meeting::query()
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Meeting $meeting) use ($ownerNames) {
                return [
                    $meeting->id,
                    $meeting->name,
                    $meeting->related_type,
                    $meeting->related_id,
                    $meeting->venue ?? ($meeting->location ?? null),
                    $meeting->start_at,
                    $meeting->finish_at,
                    $ownerNames[$meeting->user_owner_id] ?? null,
                    optional($meeting->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportTasks(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Task', 'Status', 'Priority', 'Related Type', 'Related ID',
            'Due At', 'Owner', 'Created At', 'Updated At',
        ];

        $ownerNames = User::pluck('name', 'id');
        $query = Task::query();
        if ($view === 'my') {
            $query->where('user_owner_id', auth()->id());
        } elseif ($view === 'open') {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Completed');
            });
        } elseif ($view === 'completed') {
            $query->where('status', 'Completed');
        }
        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('status', 'like', $term);
            });
        }

        $rows = $query->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Task $task) use ($ownerNames) {
                return [
                    $task->id,
                    $task->name,
                    $task->status,
                    $task->priority,
                    $task->related_type,
                    $task->related_id,
                    $task->due_at,
                    $ownerNames[$task->user_owner_id] ?? null,
                    optional($task->created_at)?->format('Y-m-d H:i:s'),
                    optional($task->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportNotes(string $view = 'all', string $criteria = ''): array
    {
        $headings = ['ID', 'Related Type', 'Related ID', 'Content', 'Created By', 'Created At', 'Updated At'];
        $userNames = User::pluck('name', 'id');

        $rows = Note::query()
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Note $note) use ($userNames) {
                return [
                    $note->id,
                    $note->related_type,
                    $note->related_id,
                    trim(strip_tags((string) $note->content)),
                    $userNames[$note->created_by] ?? null,
                    optional($note->created_at)?->format('Y-m-d H:i:s'),
                    optional($note->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportProducts(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Product Name', 'Product Code', 'Type', 'Unit Price', 'Status',
            'Category', 'Owner', 'Created At', 'Updated At',
        ];

        $rows = Product::query()
            ->with(['category', 'owner'])
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Product $product) {
                return [
                    $product->id,
                    $product->product_name,
                    $product->product_code,
                    $product->product_type,
                    $product->unit_price,
                    $product->product_status,
                    optional($product->category)->category_name,
                    optional($product->owner)->name,
                    optional($product->created_at)?->format('Y-m-d H:i:s'),
                    optional($product->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportProductCategories(string $view = 'all', string $criteria = ''): array
    {
        $headings = ['ID', 'Category Name', 'Status', 'Created At', 'Updated At'];

        $rows = ProductCategory::query()
            ->orderBy('category_name')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (ProductCategory $cat) {
                return [
                    $cat->id,
                    $cat->category_name,
                    $cat->status,
                    optional($cat->created_at)?->format('Y-m-d H:i:s'),
                    optional($cat->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportQuotations(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Quote No', 'Subject', 'Status', 'Deal ID', 'Organization',
            'Grand Total', 'Valid Until', 'Created At', 'Updated At',
        ];

        $rows = Quotation::query()
            ->with('organization')
            ->orderByDesc('id')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (Quotation $q) {
                return [
                    $q->id,
                    $q->quote_no,
                    $q->subject ?? $q->quotation_title,
                    $q->status,
                    $q->deal_id,
                    optional($q->organization)->name,
                    is_numeric($q->grand_total) ? MoneyFormatter::formatIndian($q->grand_total) : $q->grand_total,
                    optional($q->valid_until)?->format('Y-m-d'),
                    optional($q->created_at)?->format('Y-m-d H:i:s'),
                    optional($q->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }

    protected function exportIndustries(string $view = 'all', string $criteria = ''): array
    {
        $headings = ['ID', 'Name', 'Description', 'Created At', 'Updated At'];

        $rows = Industry::query()
            ->orderBy('name')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(fn (Industry $i) => [
                $i->id,
                $i->name,
                $i->description,
                optional($i->created_at)?->format('Y-m-d H:i:s'),
                optional($i->updated_at)?->format('Y-m-d H:i:s'),
            ])
            ->all();

        return [$headings, $rows];
    }

    protected function exportLeadSources(string $view = 'all', string $criteria = ''): array
    {
        $headings = ['ID', 'Name', 'Created At', 'Updated At'];

        $rows = LeadSource::query()
            ->orderBy('name')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(fn (LeadSource $s) => [
                $s->id,
                $s->name,
                optional($s->created_at)?->format('Y-m-d H:i:s'),
                optional($s->updated_at)?->format('Y-m-d H:i:s'),
            ])
            ->all();

        return [$headings, $rows];
    }

    protected function exportUsers(string $view = 'all', string $criteria = ''): array
    {
        $headings = [
            'ID', 'Name', 'Email', 'Role Type', 'Sales Target', 'Status', 'Created At',
        ];

        $roleMap = [
            0 => 'Super Admin',
            1 => 'Admin',
            2 => 'Manager',
            3 => 'Employee',
        ];

        $query = User::query()->where('crm_role_type', '!=', 0);
        if ($view === 'active') {
            $query->where('status', 1);
        }
        if ($criteria !== '') {
            $term = '%' . $criteria . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        $rows = $query->orderBy('name')
            ->limit(self::MAX_RECORDS)
            ->get()
            ->map(function (User $user) use ($roleMap) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $roleMap[(int) $user->crm_role_type] ?? $user->crm_role_type,
                    is_numeric($user->sales_target) ? MoneyFormatter::formatIndian($user->sales_target) : $user->sales_target,
                    (int) $user->status === 1 ? 'Active' : 'Inactive',
                    optional($user->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->all();

        return [$headings, $rows];
    }
}
