<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Stage;
use App\Support\RecordAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class DealsController extends Controller
{
    // Deals related APIs will be implemented here in the future
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        // Build base query based on role
        $query = Deal::query();
        if (RecordAccess::isAdmin($user)) {
            // Admin/Super Admin: all deals
        } elseif (RecordAccess::isManager($user)) {
            $employeeIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id');
            $query->where(function($q) use ($user, $employeeIds) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id)
                  ->orWhereIn('created_by', $employeeIds)
                  ->orWhereIn('user_owner_id', $employeeIds);
            });
        } else {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('user_owner_id', $user->id);
            });
        }

        // Filters
        if ($request->filled('title')) {
            $search = $request->input('title');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")

                ->orWhereHas('person', function ($personQuery) use ($search) {
                    $personQuery->whereRaw(
                        "CONCAT_WS(' ', first_name, last_name) LIKE ?",
                        ["%$search%"]
                    );
                })

                ->orWhereHas('organization', function ($orgQuery) use ($search) {
                    $orgQuery->where('name', 'like', "%$search%");
                });
            });
        }
        if ($request->filled('category')) {
            $category = $request->input('category');
            if ($category) {
                $query->whereRaw("FIND_IN_SET(?, category)", [$category]);
            }
        }
        if ($request->filled('lead_source')) {
            $leadSource = $request->input('lead_source');
            if (is_array($leadSource)) {
                $query->whereIn('lead_source', $leadSource);
            } else {
                $query->where('lead_source', $leadSource);
            }
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->input('from_date'))));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->input('to_date'))));
        }

        // Apply pagination
        $perPage = $request->input('per_page', 15); // Default to 15 items per page
        $deals = $query->with(['stage', 'organization', 'person', 'owner', 'dealSource'])
            ->orderBy('stage_id', 'asc')
            ->paginate($perPage);

        $deals->setCollection(
            $deals->getCollection()->map(function (Deal $deal) {
                $data = $deal->toArray();
                $data['stage_id'] = $deal->stage_id;
                $data['stage'] = $deal->stage_name; // stage name string for mobile
                $data['stage_name'] = $deal->stage_name;
                return $data;
            })
        );

        return response()->json([
            'success' => true,
            'deals' => $deals,
        ]);
    }

    // Deal detail api
    public function show($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);    
        }
        if (!$user->hasCrmPermission('view_crm_deals_guard')) {
            return $this->permissionDenied();
        }
        $deal = Deal::find($id);
        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);        
        }
        if (!RecordAccess::canAccessDeal($user, $deal)) {
            return $this->accessDenied();
        }
        //seperate by comma and get category names
        $category = $deal->category 
                    ? implode(', ', \App\Models\ProductCategory::whereIn('id', explode(',', $deal->category))->pluck('category_name')->toArray())
                    : null;

        //All tasks related to the deal
        $tasks = \App\Models\Task::where('related_type', 'deal')->where('related_id', $deal->id)->get()->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->name,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d M Y h:i A') : '-',
                'assigned_id' => $task->user_owner_id,
                'assigned_name' => \App\Models\User::find($task->user_owner_id)->name ?? null,
                'created_by' => $task->created_by,
                'created_by_name' => \App\Models\User::find($task->created_by)->name ?? null,
                'created_at' => $task->created_at ? \Carbon\Carbon::parse($task->created_at)->format('d M Y h:i A') : null,
                'updated_at' => $task->updated_at ? \Carbon\Carbon::parse($task->updated_at)->format('d M Y h:i A') : null,
            ];
        });

        //All Calls related to the deal
        $calls = \App\Models\Call::where('related_type', 'deal')->where('related_id', $deal->id)->get()->map(function($call) {
            $participants = $call->user_restored_id ? \App\Models\Person::where('id', $call->user_restored_id)->get()->map(function($person) {
                    return $person->first_name . ' ' . $person->last_name;
                })->toArray() : null;
            return [
                'id' => $call->id,
                'subject' => $call->name,
                'description' => $call->description,
                'started_at' => $call->start_at ? \Carbon\Carbon::parse($call->start_at)->format('d-m-Y h:i A') : '-',
                'finished_at' => $call->finish_at ? \Carbon\Carbon::parse($call->finish_at)->format('d-m-Y h:i A') : '-',
                'assigned_id' => $call->user_owner_id,
                'assigned_name' => \App\Models\User::find($call->user_owner_id)->name ?? null,
                'participants_id' => $call->user_restored_id ? json_decode($call->user_restored_id) : null,
                'participants_name' => $participants ? implode(', ', $participants) : null,
                'created_by' => $call->created_by,
                'created_by_name' => \App\Models\User::find($call->created_by)->name ?? null,
                'created_at' => $call->created_at ? \Carbon\Carbon::parse($call->created_at)->format('d-m-Y h:i A') : null,
                'updated_at' => $call->updated_at ? \Carbon\Carbon::parse($call->updated_at)->format('d-m-Y h:i A') : null,
            ];
        });

        //All Notes related to the deal
        $notes = \App\Models\Note::where('related_type', 'deal')->where('related_id', $deal->id)->get()->map(function($note) {
            return [
                'id' => $note->id,
                'content' => $note->content,
                'created_by' => $note->created_by,
                'created_by_name' => \App\Models\User::find($note->created_by)->name ?? null,
                'created_at' => $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y h:i A') : null,
                'updated_at' => $note->updated_at ? \Carbon\Carbon::parse($note->updated_at)->format('d M Y h:i A') : null,
            ];
        });

        //All Meetings related to the deal
        $meetings = \App\Models\Meeting::where('related_type', 'deal')->where('related_id', $deal->id)->get()->map(function($meeting) {
            //particiapnt user ids find participant table and get user names

            $particpant = \App\Models\Participant::where('meeting_id', $meeting->id)->get()->map(function($participant) {
                $users = [];
                if($participant->type == 'user'){
                    $user = \App\Models\User::find($participant->user_id);
                    $users[] = $user ? $user->name : null;
                    
                }
                if($participant->type == 'contact'){
                    $contact = \App\Models\Person::find($participant->person_id);
                    $users[] = $contact ? $contact->first_name . ' ' . $contact->last_name : null;
                }
                //users array returns comma separated if multiple participants, else returns single name or null
                $users = array_filter($users);
                $users = count($users) > 1 ? implode(', ', $users) : ($users[0] ?? null);
                
                //id, name, type of participant
                $participantData = [
                    'id' => $participant->type == 'user' ? $participant->user_id : ($participant->type == 'contact' ? $participant->person_id : $participant->id),
                    'name' => $users,
                    'type' => $participant->type,
                ];
                return $participantData;
            })->filter()->values()->all();
            
            return [
                'id' => $meeting->id,
                'subject' => $meeting->name,
                'description' => $meeting->description,
                'start_time' => $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('d-m-Y h:i A') : '-',
                'end_time' => $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('d-m-Y h:i A') : '-',
                'assigned_id' => $meeting->user_owner_id,
                'assigned_name' => \App\Models\User::find($meeting->user_owner_id)->name ?? null,
                'participants' =>  $particpant,
                'venue' => $meeting->venue,
                'location' => $meeting->location,
                'created_by' => $meeting->created_by,       
                'created_by_name' => \App\Models\User::find($meeting->created_by)->name ?? null,
                'created_at' => $meeting->created_at ? \Carbon\Carbon::parse($meeting->created_at)->format('d-m-Y h:i A') : null,
                'updated_at' => $meeting->updated_at ? \Carbon\Carbon::parse($meeting->updated_at)->format('d-m-Y h:i A') : null,
            ];
        });

        //All files related to the deal
        $files = \App\Models\File::where('related_type', 'deal')->where('related_id', $deal->id)->get()->map(function($file) {
            return [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_path' => $file->file_path,
                'created_by' => $file->created_by,
                'created_by_name' => \App\Models\User::find($file->created_by)->name ?? null,
                'created_at' => $file->created_at ? \Carbon\Carbon::parse($file->created_at)->format('d M Y h:i A') : null,
                'updated_at' => $file->updated_at ? \Carbon\Carbon::parse($file->updated_at)->format('d M Y h:i A') : null,
            ];
        });

        // Prepare related names (assuming relationships are defined in Deal model)
        $dealData = [
            'id' => $deal->id,
            'title' => $deal->title,
            'organization_id' => $deal->organization_id,
            'organization' => $deal->organization->name ?? null,
            'customer_id' => $deal->customer_id,
            'customer' =>  $deal->customer->name ?? null,
            'people_id' => $deal->people_id,
            'person' => $deal->person->id ? trim($deal->person->first_name. ' ' . $deal->person->last_name) : null,
            'amount' => $deal->amount,
            'status' => $deal->status,
            'reason_for_loss' => $deal->reason_for_loss,
            'label' => $deal->label,
            'expected_close' => $deal->expected_close,
            'converted_at' => $deal->converted_at,
            'description' => $deal->description,
            'lead_source_id' => $deal->lead_source,
            'lead_source' => $deal->leadSource->name ?? null,
            'category' => $category,
            'category_ids' => $deal->category ?? null,
            'assigned_id' => $deal->assigned_id,
            'assigned_name' => \App\Models\User::find($deal->assigned_id)->name ?? null,
            'user_owner_id' => $deal->user_owner_id,
            'owner' => $deal->owner->name ?? null,
            'stage_id' => $deal->stage_id,
            'stage' => $deal->stage_name,
            'probability' => $deal->probability,
            'close_date' => $deal->close_date,
            'created_by' => $deal->created_by,
            'created_by_name' => \App\Models\User::find($deal->created_by)->name ?? null,
            'created_at' => $deal->created_at,
            'updated_at' => $deal->updated_at,
            'updated_by' => $deal->updated_by,
            'deleted_by' => $deal->deleted_by,
            'deleted_at' => $deal->deleted_at,
            'notes' => $notes,
            'tasks' => $tasks,
            'calls' => $calls,
            'meetings' => $meetings,
            'files' => $files,
        ];
        return response()->json([
            'success' => true,
            'deal' => $dealData,
        ]);
    }

    //Add deal api 
    public function store(Request $request)
    {
        // Implementation for adding a new deal will go here
        // You can add validation and logic here
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        
        // Validate request data
         $messages = [
            'organization_id.required' => 'Please select or enter a company name',
            'organization_id.string' => 'Company name must be text',
            'organization_id.max' => 'Company name may not exceed 255 characters',
            'customer_id.string' => 'Company owner name must be text',
            'customer_id.max' => 'Company owner name may not exceed 255 characters',
            'people_id.required' => 'Please select or enter a contact person',
            'people_id.string' => 'Contact person name must be text',
            'people_id.max' => 'Contact person name may not exceed 255 characters',
            'amount.required' => 'Please enter the deal amount',
            'amount.numeric' => 'Deal amount must be a valid number',
            'probability.integer' => 'Probability must be a whole number',
            'probability.min' => 'Probability must be between 0 and 100',
            'probability.max' => 'Probability must be between 0 and 100',
            'label.required' => 'Please select a priority level',
            'label.string' => 'Priority must be selected from the list',
            'title.required' => 'Please enter the deal title',
            'title.string' => 'Deal title must be text',
            'title.max' => 'Deal title may not exceed 255 characters',
            'description.string' => 'Description must be text',
            'lead_source.required' => 'Please select a lead source',
            'lead_source.string' => 'Lead source must be selected from the list',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner does not exist',
            'stage_id.required' => 'Please select a deal stage',
            'stage_id.exists' => 'Please select a valid deal stage',
            'close_date.required' => 'Please select an expected close date',
            'close_date.date' => 'Expected close date must be a valid date',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        $stageIdResolved = Stage::resolveId($request->input('stage_id', $request->input('stage', '')));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

         $validated = $request->validate([
            'organization_id' => 'required|max:255',
            'customer_id' => 'nullable|max:255',
            'people_id' => 'required|max:255',
            'amount' => 'required|numeric',
            // 'status' => 'nullable|string',
            'label' => 'required',

            'title' => 'required|max:255',
            'description' => 'nullable|string',

            'lead_source' => 'required',
            'user_owner_id' => 'required|exists:users,id',
            'stage_id' => 'required|integer|exists:stages,id',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',

            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        if (!$stageIdResolved) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid deal stage',
            ], 422);
        }

        $organizationId = $validated['organization_id'];
        $peopleId = $validated['people_id'];
        // Server-side duplicate prevention: title + organization + person
        if (!empty($validated['title']) && $organizationId && $peopleId) {
            $existingDeal = Deal::where('title', $validated['title'])
                ->where('organization_id', $validated['organization_id'])
                ->where('people_id', $validated['people_id'])
                ->first();
            if ($existingDeal) {
                return response()->json([
                    'success' => false,
                    'message' => 'A deal with the same title, organization, and contact person already exists.',
                ], 409); // Conflict
            }
        }

        // Determine status from provided stage (so creating with Closed Won/Lost sets status correctly)
        $stageLowerCreate = $stageNameResolved ? strtolower(trim($stageNameResolved)) : '';
        $statusToSave = 'open';
        if ($stageLowerCreate === 'closed won') {
            $statusToSave = 'Closed won';
        } elseif ($stageLowerCreate === 'closed lost') {
            $statusToSave = 'Closed lost';
        }
        
        // Logic to create and save the new deal will go here
        $deal = new Deal();
        $deal->organization_id = $validated['organization_id'];
        $deal->customer_id = $validated['customer_id'] ?? null;
        $deal->people_id = $validated['people_id'];
        $deal->amount = $validated['amount'];
        $deal->status = $statusToSave;
        $deal->label = $validated['label'];
        $deal->title = $validated['title'];
        $deal->description = $validated['description'] ?? null;
        $deal->lead_source = $validated['lead_source'];
        $deal->category = implode(',', $validated['categories']); // Store as comma-separated string
        $deal->user_owner_id = $validated['user_owner_id']; 
        $deal->stage_id = $stageIdResolved;
        $deal->probability = $validated['probability'] ?? null;
        $deal->close_date = date('Y-m-d', strtotime($validated['close_date']));
        $deal->reason_for_loss = $validated['reason_for_loss'] ?? null;
        $deal->created_at = now();
        $deal->updated_at = now();
        $deal->created_by = $user->id;
        $deal->save();

        // If created as Closed Won, update monthly sales for the owner
        if ($statusToSave === 'Closed won' && $deal->user_owner_id && $deal->amount) {
            $user = \App\Models\User::find($deal->user_owner_id);
            $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
            $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
            $year = $closeDate->year;
            $month = $closeDate->month;
            \App\Models\UserMonthlySales::updateOrCreate(
                [
                    'user_id' => $deal->user_owner_id,
                    'year' => $year,
                    'month' => $month
                ],
                [
                    'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                    'sales_target' => $salesTarget
                ]
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Deal created successfully',
            'deal_id' => $deal->id,
            'stage_id' => $deal->stage_id,
            'stage' => $stageNameResolved,
        ], 201);


    }

    //Update deal api
    public function update(Request $request, $id)
    {
        // Implementation for updating an existing deal will go here
        // You can add validation and logic here
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        if (!$user->hasCrmPermission('edit_crm_deals_guard')) {
            return $this->permissionDenied();
        }

        // Further implementation will go here
        $deal = Deal::find($id);
        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);        
        }   
        if (!RecordAccess::canAccessDeal($user, $deal)) {
            return $this->accessDenied();
        }
        
        
        $messages = [
            'organization_id.required' => 'Please select or enter a company name',
            'organization_id.string' => 'Company name must be text',
            'organization_id.max' => 'Company name may not exceed 255 characters',
            'customer_id.string' => 'Company owner name must be text',
            'customer_id.max' => 'Company owner name may not exceed 255 characters',
            'people_id.required' => 'Please select or enter a contact person',
            'people_id.string' => 'Contact person name must be text',
            'people_id.max' => 'Contact person name may not exceed 255 characters',
            'amount.required' => 'Please enter the deal amount',
            'amount.numeric' => 'Deal amount must be a valid number',
            'probability.integer' => 'Probability must be a whole number',
            'probability.min' => 'Probability must be between 0 and 100',
            'probability.max' => 'Probability must be between 0 and 100',
            'label.required' => 'Please select a priority level',
            'label.string' => 'Priority must be selected from the list',
            'title.required' => 'Please enter the deal title',
            'title.string' => 'Deal title must be text',
            'title.max' => 'Deal title may not exceed 255 characters',
            'description.string' => 'Description must be text',
            'lead_source.required' => 'Please select a lead source',
            'lead_source.string' => 'Lead source must be selected from the list',
            'user_owner_id.required' => 'Please select an account owner',
            'user_owner_id.exists' => 'Selected account owner does not exist',
            'stage_id.required' => 'Please select a deal stage',
            'stage_id.exists' => 'Please select a valid deal stage',
            'close_date.required' => 'Please select an expected close date',
            'close_date.date' => 'Expected close date must be a valid date',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];

        // Removed debug code causing memory exhaustion
        $stageIdResolved = Stage::resolveId($request->input('stage_id', $request->input('stage', '')));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

        $validated = $request->validate([
            'organization_id' => 'required|max:255',
            'customer_id' => 'nullable|max:255',
            'people_id' => 'required|max:255',
            'amount' => 'required|numeric',
            'status' => 'nullable|string',
            'label' => 'required|string',

            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'lead_source' => 'required',
            'user_owner_id' => 'required|exists:users,id',
            'stage_id' => 'required|integer|exists:stages,id',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
        ], $messages);

        if (!$stageIdResolved) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid deal stage',
            ], 422);
        }

        $status = $validated['status'] ?? $deal->status;
        $stageChanged = (int) $stageIdResolved !== (int) $deal->stage_id;
        $wasClosedWon = strtolower((string) $deal->stage_name) === 'closed won';
        $isClosedWon = $stageNameResolved && strtolower($stageNameResolved) === 'closed won';
        if ($stageNameResolved) {
            if ($isClosedWon) {
                $status = 'Closed won';
            } elseif (strtolower($stageNameResolved) === 'closed lost') {
                $status = 'Closed lost';
            } else {
                $status = 'open';
            }
        }

        $deal->organization_id = $validated['organization_id'];
        $deal->customer_id = $validated['customer_id'] ?? null;
        $deal->people_id = $validated['people_id'];    
        $deal->amount = $validated['amount'];
        $deal->status = $status;
        $deal->label = $validated['label'];
        $deal->title = $validated['title'];
        $deal->description = $validated['description'] ?? null; 
        $deal->lead_source = $validated['lead_source'];
        $deal->category = implode(',', $validated['categories']); // Store as comma-separated string
        $deal->user_owner_id = $validated['user_owner_id']; 
        $deal->stage_id = $stageIdResolved;
        $deal->probability = $validated['probability'] ?? null;
        $deal->close_date = date('Y-m-d', strtotime($validated['close_date']));
        $deal->reason_for_loss = $validated['reason_for_loss'] ?? null;
        $deal->updated_by = $user->id;
        $deal->updated_at = now();
        $deal->save();

        // Record stage change in stage_history if changed
        if ($stageChanged) {
            \App\Models\StageHistory::create([
                'deal_id' => $deal->id,
                'stage_name' => $stageNameResolved,
                'amount' => $deal->amount,
                'probability' => $deal->probability,
                'close_date' => $deal->close_date,
                'modified_time' => now(),
                'modified_by' => $user->id,
            ]);
            // Only update monthly sales if moving to Closed Won and was not already Closed Won
            if ($isClosedWon && !$wasClosedWon && $deal->user_owner_id && $deal->amount) {
                    $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                    $year = $closeDate->year;
                    $month = $closeDate->month;
                    $user = \App\Models\User::find($deal->user_owner_id);
                    $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                    \App\Models\UserMonthlySales::updateOrCreate(
                        [
                            'user_id' => $deal->user_owner_id,
                            'year' => $year,
                            'month' => $month
                        ],
                        [
                            'achieved_sales' => \DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                            'sales_target' => $salesTarget
                        ]
                    );
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Deal updated successfully',
            'deal_id' => $deal->id,
            'stage_id' => $deal->stage_id,
            'stage' => $stageNameResolved,
        ]);

    }

    //convert leads into deals api
    public function storeFromLead(Request $request, $leadId)
    {

        
        // Implementation for converting a lead into a deal will go here
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('convert_crm_leads_to_deals_guard')) {
            return $this->permissionDenied();
        }

         // Prefer stage_id; legacy `stage` (id or name) still accepted as fallback
        $stageIdResolved = Stage::resolveId($request->input('stage_id', $request->input('stage', '')));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric',
            'label' => 'nullable|string',
            'lead_source' => 'nullable|string',
            'user_owner_id' => 'nullable|exists:users,id',
            'stage_id' => 'required|integer|exists:stages,id',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'required|date',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
        ]);

        if (!$stageIdResolved) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid deal stage',
            ], 422);
        }
        $validated['stage_id'] = $stageIdResolved;
        $validated['stage_name'] = $stageNameResolved;
        // Load the lead early so we can perform duplicate checks using its organization/person
        $lead = Lead::findOrFail($leadId);
        if (!RecordAccess::canAccessLead($user, $lead)) {
            return $this->accessDenied();
        }
       // Duplicate prevention: title + organization + person
        $titleToCheck = $validated['title'] ?? $lead->getAttribute('title');
        $orgId = $lead->getAttribute('organization_id');
        $peopleId = $lead->getAttribute('people_id');
        if ($titleToCheck && $orgId && $peopleId) {
            $exists = \App\Models\Deal::whereRaw('LOWER(title) = ?', [strtolower($titleToCheck)])
                ->where('organization_id', $orgId)
                ->where('people_id', $peopleId)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A deal with the same title, organization, and contact person already exists.',
                ], 409); // Conflict
            }
        }

        \DB::transaction(function () use ($request, $leadId, $validated, $lead) {
            // Determine status based on stage
            $stageLower = isset($validated['stage_name']) ? strtolower($validated['stage_name']) : '';
            $status = 'open';
            if ($stageLower === 'closed won') {
                $status = 'Closed won';
            } elseif ($stageLower === 'closed lost') {
                $status = 'Closed lost';
            }

            $ownerId = $validated['user_owner_id'] ?? $lead->getAttribute('user_owner_id');

            $deal = Deal::create([
                'title' => $validated['title'] ?? $lead->getAttribute('title'),
                'description' => $validated['description'] ?? $lead->getAttribute('description'),
                'amount' => $validated['amount'] ?? $lead->getAttribute('amount'),
                'probability' => $validated['probability'] ?? 10,
                'label' => $validated['label'] ?? $lead->getAttribute('label'),
                'lead_source' => $lead->getAttribute('lead_source'),
                'category' => $lead->getAttribute('category'),
                'organization_id' => $lead->getAttribute('organization_id'),
                'customer_id' => $lead->getAttribute('customer_id'),
                'people_id' => $lead->getAttribute('people_id'),
                'user_owner_id' => $ownerId,
                'assigned_id' => $lead->getAttribute('assigned_id'),
                'stage_id' => $validated['stage_id'] ?? null,
                'close_date' => Carbon::parse($validated['close_date'])   ?? null,
                'status' => $status,
                'reason_for_loss' => $validated['reason_for_loss'] ?? null,
                'created_by' => $lead->getAttribute('created_by'),
                'created_at' => now(),
                'updated_at' => now(),
                'converted_lead_id' => $lead->id,
            ]);

            // If converted to Closed Won, update monthly sales for the owner
            if ($status === 'Closed won' && $deal->user_owner_id && $deal->amount) {
                $user = \App\Models\User::find($deal->user_owner_id);
                $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                $year = $closeDate->year;
                $month = $closeDate->month;
                \App\Models\UserMonthlySales::updateOrCreate(
                    [
                        'user_id' => $deal->user_owner_id,
                        'year' => $year,
                        'month' => $month
                    ],
                    [
                        'achieved_sales' => DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                        'sales_target' => $salesTarget
                    ]
                );
            }

            $lead->update(['converted_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to deal successfully',
        ]);


    }

    // Delete deal api
    public function destroy($id)
    {
        // Implementation for deleting a deal will go here
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('delete_crm_deals_guard')) {
            return $this->permissionDenied();
        }
        $deal = Deal::find($id);
        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);        
        }
        if (!RecordAccess::canAccessDeal($user, $deal)) {
            return $this->accessDenied();
        }
        $deal->deleted_by = $user->id;
        $deal->save();
        $deal->delete();    

        return response()->json([
            'success' => true,
            'message' => 'Deal deleted successfully',
        ]);
    }

    //Deals Stage Switching API
    public function switchStage(Request $request, $id)
    {
        // Implementation for switching the stage of a deal will go here
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('edit_crm_deals_guard') && !$user->hasCrmPermission('manage_crm_deals_guard')) {
            return $this->permissionDenied();
        }
        $deal = Deal::find($id);
        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);
        }
        if (!RecordAccess::canAccessDeal($user, $deal)) {
            return $this->accessDenied();
        }
         $messages = [
            'probability.integer' => 'Probability must be a whole number',
            'probability.min' => 'Probability must be between 0 and 100',
            'probability.max' => 'Probability must be between 0 and 100',
            'stage_id.required' => 'Please select a deal stage',
            'stage_id.exists' => 'Please select a valid deal stage',
            'reason_for_loss.required_if' => 'Reason for loss is required when stage is Closed Lost',
            'reason_for_loss.string' => 'Reason for loss must be text',
            'reason_for_loss.max' => 'Reason for loss may not exceed 255 characters',
            'close_date.required_if' => 'Close date is required when stage is Closed Won',
            'close_date.date' => 'Close date must be a valid date',
            'amount.required_if' => 'Amount is required when stage is Closed Won',
            'amount.numeric' => 'Amount must be a valid number',
        ];

        // Removed debug code causing memory exhaustion
        $stageIdResolved = Stage::resolveId($request->input('stage_id', $request->input('stage', '')));
        $stageNameResolved = $stageIdResolved ? Stage::nameById($stageIdResolved) : null;
        $isClosedLost = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed lost';
        $isClosedWon = $stageNameResolved && strtolower(trim($stageNameResolved)) === 'closed won';

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:stages,id',
            'probability' => 'nullable|integer|min:0|max:100',
            'reason_for_loss' => $isClosedLost ? 'required|string|max:255' : 'nullable|string|max:255',
            'close_date' => $isClosedWon ? 'required|date' : 'nullable|date',
            'amount' => $isClosedWon ? 'required|numeric' : 'nullable|numeric',
        ], $messages);

        if (!$stageIdResolved) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid deal stage',
            ], 422);
        }

        $status = $deal->status;
        $stageChanged = (int) $stageIdResolved !== (int) $deal->stage_id;
        $wasClosedWon = strtolower((string) $deal->stage_name) === 'closed won';
       
        if ($stageNameResolved) {
            if ($isClosedWon) {
                $status = 'Closed won';
            } elseif ($isClosedLost) {
                $status = 'Closed lost';
            } else {
                $status = 'open';
            }
        }

        $deal->stage_id = $stageIdResolved;
        $deal->probability = $validated['probability'] ?? $deal->probability;
        $deal->status = $status;
        $deal->updated_by = $user->id;
        $deal->updated_at = now();
        if($isClosedWon){
            $deal->close_date = date('Y-m-d', strtotime($validated['close_date']));
            $deal->amount = $validated['amount'];
        }
        if($isClosedLost){
            $deal->reason_for_loss = $validated['reason_for_loss'];
        }
        $deal->save();

        // Record stage change in stage_history if changed
        if ($stageChanged) {
            \App\Models\StageHistory::create([
                'deal_id' => $deal->id,
                'stage_name' => $stageNameResolved,
                'amount' => $deal->amount,
                'probability' => $deal->probability,
                'close_date' => $deal->close_date,
                'modified_time' => now(),
                'modified_by' => $user->id,
            ]);
            // Only update monthly sales if moving to Closed Won and was not already Closed Won
            if ($isClosedWon && !$wasClosedWon && $deal->user_owner_id && $deal->amount) {
                    $closeDate = $deal->close_date ? \Carbon\Carbon::parse($deal->close_date) : now();
                    $year = $closeDate->year;
                    $month = $closeDate->month;
                    $user = \App\Models\User::find($deal->user_owner_id);
                    $salesTarget = $user ? ($user->sales_target ?? 0) : 0;
                    \App\Models\UserMonthlySales::updateOrCreate(
                        [
                            'user_id' => $deal->user_owner_id,
                            'year' => $year,
                            'month' => $month
                        ],
                        [
                            'achieved_sales' => \DB::raw('achieved_sales + ' . (float) ($deal->amount ?? 0)),
                            'sales_target' => $salesTarget
                        ]
                    );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Deal stage updated successfully',
            'data' => [
                'id' => $deal->id,
                'stage_id' => $deal->stage_id,
                'stage' => $stageNameResolved,
                'status' => $deal->status,
                'probability' => $deal->probability,
                'amount' => $deal->amount,
                'close_date' => $deal->close_date,
                'reason_for_loss' => $deal->reason_for_loss,
            ],
        ]);
    }

    private function permissionDenied()
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    private function accessDenied()
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have access to this record.',
        ], 403);
    }
}