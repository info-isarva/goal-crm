<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskActionsController extends Controller
{
    public function __construct()
    {
        // Prevent editing/deleting tasks when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'update', 'complete', 'destroy'
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($task);
        // echo "Updating task ID: " . $id; // Debug line
        // print_r($request->all());
        // exit; // Stop execution to see the output
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|string|in:normal,high,highest,low,lowest',
            'status' => 'required|string|in:Not Started,Deferred,In Progress,Completed,Waiting for input',
            'due_at' => 'required|date',
            'related_type' => 'required|string|max:50',
            'related_id' => 'required|integer',
            'reminder_notifications_enabled' => 'nullable|boolean',
            'reminder_offset' => 'nullable|integer|min:0',
            'user_assigned_id' => 'nullable|integer',
        ]);
        \App\Support\RecordAccess::authorizeRelated($validated['related_type'], $validated['related_id']);
        if ($validated['status'] === 'Completed') {
            $completed_at = now();
        } else {
            $completed_at = null;
        }
        $task->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'due_at' => $validated['due_at'],
            'priority' => $validated['priority'],
            'completed_at' => $completed_at,
            'status' => $validated['status'],
            'related_type' => $validated['related_type'],
            'related_id' => $validated['related_id'],
            
            'user_assigned_id' => $validated['user_assigned_id'],
            'reminder_notifications_enabled' => $validated['reminder_notifications_enabled'] ?? ($task->reminder_notifications_enabled ?? true),

            'updated_by' => Auth::id(),
        ]);

        // Manage reminders based on the flag
        if (($validated['reminder_notifications_enabled'] ?? ($task->reminder_notifications_enabled ?? true)) && $validated['status'] !== 'Completed') {
            // recreate reminder with provided offset (or default 30)
            try {
                $task->reminders()->delete();
            } catch (\Exception $e) {
                // ignore
            }
            $offset = $request->input('reminder_offset', 30);
            $task->createReminder($offset);
        } 
        
        // else {
        //     // delete existing reminders if reminders disabled or task completed
        //     try {
        //         $task->reminders()->delete();
        //     } catch (\Exception $e) {
        //         // ignore
        //     }
        // }
        return redirect()->back()->with('success', 'Task updated successfully');
    }

    public function complete($id)
    {
        $task = Task::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($task);
        $task->update([
            'completed_at' => now(),
            'status' => 'Completed',
            'updated_by' => Auth::id(),
        ]);
        return redirect()->back()->with('success', 'Task marked as completed');
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        \App\Support\RecordAccess::authorizeActivity($task);
        $task->update(['deleted_by' => Auth::id()]);
        $task->delete();
        return redirect()->back()->with('success', 'Task deleted');
    }
}
