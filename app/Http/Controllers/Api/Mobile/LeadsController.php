<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadsController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }

        $leads = []; // Placeholder for leads data

        $query = Lead::query();
        if (RecordAccess::isAdmin($user)) {
            // Admin/Super Admin: all leads
        } else {
            // $isManager = \App\Models\User::where('assign_manager', $user->id)->exists();
            // if ($isManager) {
            //     $teamUserIds = \App\Models\User::where('assign_manager', $user->id)->pluck('id')->toArray();
            //     $query->where(function($q) use ($user, $teamUserIds) {
            //         $q->orWhere('created_by', $user->id)
            //           ->orWhere('assigned_id', $user->id)
            //           ->orWhereIn('created_by', $teamUserIds)
            //           ->orWhereIn('assigned_id', $teamUserIds);
            //     });
            // } else {
            //     $query->where(function($q) use ($user) {
            //         $q->where('created_by', $user->id)
            //           ->orWhere('assigned_id', $user->id);
            //     });
            // }

            if (!RecordAccess::isManager($user)) {
                if( $user->email === 'marketing@isarvait.com') {
                    $query->where(function ($q) use ($user) {
                        $q->where('created_by', $user->id)
                            ->orWhere('user_owner_id', $user->id)
                            ->orWhereNull('created_by')
                            ->orWhere('created_by', '')
                            ->orWhereNull('user_owner_id')
                            ->orWhere('user_owner_id', '');
                    });
                }else {
                    $query->where(function ($q) use ($user) {
                        $q->where('created_by', $user->id)
                            ->orWhere('user_owner_id', $user->id);
                    });
                }
            } else { // Manager

                $employeeIds = User::where('assign_manager', $user->id)->pluck('id');
                $query->where(function ($q) use ($user, $employeeIds) {
                    $q->where('created_by', $user->id)
                        ->orWhere('user_owner_id', $user->id)
                        ->orWhereNull('created_by')
                        ->orWhere('created_by', '')
                        ->orWhereIn('created_by', $employeeIds)
                        ->orWhereIn('user_owner_id', $employeeIds);
                });

                \Log::info('Manager Lead Query Debug:', [
                    'manager_id' => $user->id,
                    'employee_ids' => $employeeIds,
                    'query' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ]);
            }
        }
        // Additional filters can be applied here based on request parameters
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
        $statusFilter = null;
        if ($request->filled('status')) {
            $statusFilter = $request->input('status');
            if (is_array($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            } else {
                $query->where('status', $statusFilter);
            }
        }
        if($request->filled('priority')){
            $priority = $request->input('priority');
            if (is_array($priority)) {
                $query->whereIn('label', $priority);
            } else {
                $query->where('label', $priority);
            }  
        }
        if($request->filled('owner')){
            $owner = $request->input('owner');
            if (is_array($owner)) {
                $query->whereIn('user_owner_id', $owner);
            } else {
                $query->where('user_owner_id', $owner);
            }  
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->input('from_date'))));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->input('to_date'))));
        }

        $view = $request->input('view', 'All Leads');
        // View filter
        switch ($view) {
            case 'Converted Leads':
                $query->whereNotNull('converted_at');
                break;
            case 'Junk Leads':
                $query->where('status', 'Junk Lead');
                break;
            case 'Mailing Labels':
                $query->whereNotNull('mailing_label'); // Adjust if you have a different field
                break;
            case 'My Converted Leads':
                $query->whereNotNull('converted_at')->where('user_owner_id', $user->id);
                break;
            case 'My Leads':
                $query->where('user_owner_id', $user->id);
                break;
            case 'Not Qualified Leads':
                $query->where('status', 'not_qualified');
                break;
            case 'Open Leads':
                $query->whereNotIn('status', ['Lost Lead'])
                    ->whereNull('converted_at');
                break;
            case 'Recently Created Leads':
                $query->orderBy('created_at', 'desc');
                break;
            case 'Recently Modified Leads':
                $query->orderBy('updated_at', 'desc');
                break;
            case "Today's Leads":
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'Unread Leads':
                $query->where('is_read', false); // Add this field if needed
                break;
            case 'Unsubscribed Leads':
                $query->where('is_unsubscribed', true); // Add this field if needed
                break;
            case 'All Leads':
            default:
                // No additional filter
                break;
        }

        // Hide Junk Lead by default; only show when Junk is selected in status filter or Junk Leads view
        $statusIncludesJunk = is_array($statusFilter)
            ? in_array('Junk Lead', $statusFilter, true)
            : $statusFilter === 'Junk Lead';
        if (!$statusIncludesJunk && $view !== 'Junk Leads') {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Junk Lead');
            });
        }

         // Default: show only unconverted unless view is Converted Leads/My Converted Leads
        if (!in_array($view, ['Converted Leads', 'My Converted Leads'])) {
            $query->whereNull('converted_at');
        }
        $leads = $query->orderBy('created_at', 'desc')
         ->paginate(15); // Apply lazy loading with pagination

        $leads->getCollection()->transform(function ($lead) {
            return [
                'id' => $lead->id,
                'title' => $lead->title,
                'organization_id' => $lead->organization_id,
                'company' => $lead->organization->name ?? null,
                'customer_id' => $lead->customer_id,
                'customer_name' => $lead->customer->name ?? null,
                'people_id' => $lead->people_id,
                'person_name' => $lead->person->id ? trim($lead->person->first_name. ' ' . $lead->person->last_name) : null,
                'amount' => $lead->amount,
                'status' => $lead->status,
                'reason_for_loss' => $lead->reason_for_loss,
                'label' => $lead->label,
                'expected_close' => $lead->expected_close,
                'converted_at' => $lead->converted_at,
                'description' => $lead->description,
                'lead_source_id' => $lead->lead_source,
                'lead_source' => $lead->leadSource->name ?? null,
                'category' => $lead->category ?? null,
                'assigned_id' => $lead->assigned_id,
                'assigned_name' => \App\Models\User::find($lead->assigned_id)->name ?? null,
                'user_owner_id' => $lead->user_owner_id,
                'owner' => $lead->owner->name ?? null,
                'created_by' => $lead->created_by,
                'created_by_name' => \App\Models\User::find($lead->created_by)->name ?? null,
                'created_at' => $lead->created_at,
                'phone' => $lead->person->mobile ?? $lead->person->phone ?? null,
                'email' => $lead->person->email ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'leads' => $leads,
        ]);
    }

    //Leads detail api
    public function show($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);
        }
        if (!$user->hasCrmPermission('view_crm_leads_guard')) {
            return $this->permissionDenied();
        }
        $lead = Lead::find($id);
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);    
        }
        if (!RecordAccess::canAccessLead($user, $lead)) {
            return $this->accessDenied();
        }


          //All tasks related to the lead
        $tasks = \App\Models\Task::where('related_type', 'lead')->where('related_id', $lead->id)->get()->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->name,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d M Y h:i A') : '-',
                'priority' => $task->priority,
                'assigned_id' => $task->user_owner_id,
                'assigned_name' => \App\Models\User::find($task->user_owner_id)->name ?? null,
                'created_by' => $task->created_by,
                'created_by_name' => \App\Models\User::find($task->created_by)->name ?? null,
                'created_at' => $task->created_at ? \Carbon\Carbon::parse($task->created_at)->format('d-m-Y h:i A') : null,
                'updated_at' => $task->updated_at ? \Carbon\Carbon::parse($task->updated_at)->format('d-m-Y h:i A') : null,
            ];
        });

        //All Calls related to the lead
        $calls = \App\Models\Call::where('related_type', 'lead')->where('related_id', $lead->id)->get()->map(function($call) {
           $participants = $call->user_restored_id 
            ? \App\Models\Person::where('id', $call->user_restored_id)
                ->get()
                ->map(function($person) {
                    return $person->first_name . ' ' . $person->last_name;
                })
                ->toArray()
            : null;
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

        //All Notes related to the lead
        $notes = \App\Models\Note::where('related_type', 'lead')->where('related_id', $lead->id)->get()->map(function($note) {
            return [
                'id' => $note->id,
                'content' => $note->content,
                'created_by' => $note->created_by,
                'created_by_name' => \App\Models\User::find($note->created_by)->name ?? null,
                'created_at' => $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M Y h:i A') : null,
                'updated_at' => $note->updated_at ? \Carbon\Carbon::parse($note->updated_at)->format('d M Y h:i A') : null,
            ];
        });

        //All Meetings related to the lead
        $meetings = \App\Models\Meeting::where('related_type', 'lead')->where('related_id', $lead->id)->get()->map(function($meeting) {
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

        //All files related to the lead
        $files = \App\Models\File::where('related_type', 'lead')->where('related_id', $lead->id)->get()->map(function($file) {
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

         //seperate by comma and get category names
        $category = $lead->category 
                    ? implode(', ', \App\Models\ProductCategory::whereIn('id', explode(',', $lead->category))->pluck('category_name')->toArray())
                    : null;


        // Prepare related names (assuming relationships are defined in Lead model)
        $leadData = [
            'id' => $lead->id,
            'title' => $lead->title,
            'organization_id' => $lead->organization_id,
            'organization' => $lead->organization->name ?? null,
            'customer_id' => $lead->customer_id,
            'customer' => $lead->customer->name ?? null,
            'people_id' => $lead->people_id,
            'person' => $lead->person->id ? trim($lead->person->first_name. ' ' . $lead->person->last_name) : null,
            'amount' => $lead->amount,
            'status' => $lead->status,
            'reason_for_loss' => $lead->reason_for_loss,
            'label' => $lead->label,
            'expected_close' => $lead->expected_close,
            'converted_at' => $lead->converted_at,
            'description' => $lead->description,
            'lead_source_id' => $lead->lead_source,
            'lead_source' => $lead->leadSource->name ?? null,
            'category' => $category,
            'category_ids' => $lead->category ?? null,
            'assigned_id' => $lead->assigned_id,
            'assigned_name' => \App\Models\User::find($lead->assigned_id)->name ?? null,
            'user_owner_id' => $lead->user_owner_id,
            'owner' => $lead->owner->name ?? null,
            'created_by' => $lead->created_by,
            'created_by_name' => \App\Models\User::find($lead->created_by)->name ?? null,
            'created_at' => $lead->created_at,
            'updated_at' => $lead->updated_at,
            'updated_by' => $lead->updated_by,
            'deleted_by' => $lead->deleted_by,
            'deleted_at' => $lead->deleted_at,
            'tasks' => $tasks,
            'calls' => $calls,
            'notes' => $notes,
            'meetings' => $meetings,
            'files' => $files,
            
        ];

        return response()->json([
            'success' => true,
            'lead' => $leadData,
        ]);
    }

    //Create Lead api
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);    
        }
        // Validate request data
         $messages = [
            'title.required' => 'Please enter a title for the lead.',
            'title.max' => 'Title may not be longer than 100 characters.',
            'organization_id.required' => 'Please select or enter the Company name',
            'organization_id.max' => 'Company name may not exceed 255 characters.',
            'people_id.required' => 'Please select or add a Contact Person.',
            'people_id.max' => 'Contact person value is too long.',
            'user_owner_id.required' => 'Please assign an Owner.',
            'user_owner_id.exists' => 'The selected Owner is invalid.',
            'lead_source.required' => 'Please select a Lead Source.',
            'label.required' => 'Please choose a Priority for this lead.',
            'status.required' => 'Please select a Lead Status.',
            'description.max' => 'Description may not exceed 250 characters.',
            'amount.numeric' => 'Amount must be a valid number.',

            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
            
        ];

        $validatedData = $request->validate([
            'title'           => 'required|string|max:100',
            'organization_id' => 'required|max:255',
            'people_id' => 'required|max:255',
            'customer_id' => 'nullable|max:255',
            'amount' => 'nullable|numeric|max:50000000',
            'status' => 'required',
            'label' => 'required|string',
            'lead_source' => 'required',
            'user_owner_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:250',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
            'main_category_id' => 'nullable|integer',
            
        ], $messages);

        //Server-side duplicate prevention: title + organization + person
        $organizationId = $validatedData['organization_id'];
        $peopleId = $validatedData['people_id'];
        if(!empty($validatedData['title']) && $organizationId && $peopleId) {
            $existingLead = Lead::where('title', $validatedData['title'])
                ->where('organization_id', $validatedData['organization_id'])
                ->where('people_id', $validatedData['people_id'])
                ->first();
            if ($existingLead) {
                return response()->json([
                    'success' => false,
                    'message' => 'A lead with the same Title, Company, and Contact Person already exists.',
                ], 409); // Conflict
            }
        }

        // Create new Lead
        $lead = new Lead();
        $lead->title = $validatedData['title'];
        $lead->organization_id = $validatedData['organization_id'];
        $lead->people_id = $validatedData['people_id'];
        $lead->customer_id =  null;
        $lead->amount = $validatedData['amount'] ?? null;
        $lead->status = $validatedData['status'];
        $lead->label = $validatedData['label'];
        $lead->lead_source = $validatedData['lead_source'];
        $lead->user_owner_id = $validatedData['user_owner_id'];
        $lead->assigned_id = $validatedData['user_owner_id']; // Assign to owner by default
        $lead->description = $validatedData['description'] ?? null;
        $lead->category = implode(',', $validatedData['categories']);
        $lead->created_by = $user->id;
        $lead->main_category_id = $validatedData['main_category_id'] ?? null;
        $lead->industry_id = $validatedData['main_category_id'] ?? null;
        $lead->save();
        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully',
            'lead_id' => $lead->id,
        ], 201);
    }

    //Update Lead api
    public function update(Request $request, $id)
    {
        // Similar to store method, but for updating an existing lead
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);    
        }
        if (!$user->hasCrmPermission('edit_crm_leads_guard')) {
            return $this->permissionDenied();
        }
        $lead = Lead::find($id);
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);   
        }
        if (!RecordAccess::canAccessLead($user, $lead)) {
            return $this->accessDenied();
        }
        // Validate request data
        $messages = [
            'title.required' => 'Please enter a title for the lead.',
            'title.max' => 'Title may not be longer than 100 characters.',
            'organization_id.required' => 'Please select or enter the Company name',
            'organization_id.max' => 'Company name may not exceed 255 characters.',
            'people_id.required' => 'Please select or add a Contact Person.',
            'people_id.max' => 'Contact person value is too long.',
            'user_owner_id.required' => 'Please assign an Owner.',
            'user_owner_id.exists' => 'The selected Owner is invalid.',
            'lead_source.required' => 'Please select a Lead Source.',
            'label.required' => 'Please choose a Priority for this lead.',
            'status.required' => 'Please select a Lead Status.',
            'description.max' => 'Description may not exceed 250 characters.',
            'amount.numeric' => 'Amount must be a valid number.',
            'categories.required' => 'Please select at least one category.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'Selected category is invalid.',
        ];
        $validatedData = $request->validate([
            'title'           => 'required|string|max:100',
            'organization_id' => 'required|max:255',
            'people_id' => 'required|max:255',
            'customer_id' => 'nullable|max:255',
            'amount' => 'nullable|numeric|max:50000000',
            'status' => 'required',
            'label' => 'required|string',
            'lead_source' => 'required',
            'user_owner_id' => 'required|exists:users,id',
            'description' => 'nullable|string|max:250',
            'categories' => 'required|array',
            'categories.*' => 'exists:product_categories,id',
            'main_category_id' => 'nullable|integer',
        ], $messages);  
        // Update Lead
        $lead->title = $validatedData['title'];
        $lead->organization_id = $validatedData['organization_id'];
        $lead->people_id = $validatedData['people_id'];
        $lead->customer_id =  null;
        $lead->amount = $validatedData['amount'] ?? null;
        $lead->status = $validatedData['status'];
        $lead->label = $validatedData['label'];
        $lead->lead_source = $validatedData['lead_source'];
        $lead->user_owner_id = $validatedData['user_owner_id'];
        $lead->assigned_id = $validatedData['user_owner_id'];
        $lead->description = $validatedData['description'] ?? null;
        $lead->category = implode(',', $validatedData['categories']);
        $lead->main_category_id = $validatedData['main_category_id'] ?? null;
        $lead->industry_id = $validatedData['main_category_id'] ?? null;
        $lead->updated_by = $user->id;
        $lead->updated_at = now();
        $lead->save();
        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully',
        ]); 
    }
    
    //Delete Lead api
    public function destroy($id)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing token',
            ], 401);

        }
        if (!$user->hasCrmPermission('delete_crm_leads_guard')) {
            return $this->permissionDenied();
        }
        $lead = Lead::find($id);
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }
        if (!RecordAccess::canAccessLead($user, $lead)) {
            return $this->accessDenied();
        }
        $lead->deleted_by = $user->id;
        $lead->save();
        $lead->delete();
        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully',
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