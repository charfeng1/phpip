<?php

namespace App\Http\Controllers;

use App\Enums\EventCode;
use App\Enums\UserRole;
use App\Http\Requests\ClearTasksRequest;
use App\Models\Matter;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the application dashboard and quick task operations.
 *
 * Provides overview statistics for matters by category and user task counts.
 * Includes bulk task completion functionality for the dashboard.
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard with matter and task statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Count matters per categories
        $categories = Matter::getCategoryMatterCount();
        $taskscount = Task::getUsersOpenTaskCount();

        // Pre-load initial task list (non-renewals) for faster page load
        $taskQuery = (new Task)->openTasks()->where('code', '!=', EventCode::RENEWAL->value);
        $renewalQuery = (new Task)->openTasks()->where('code', EventCode::RENEWAL->value);

        // Common filter logic for both queries
        $applyFilters = function ($query) use ($request) {
            $user = Auth::user();

            // Apply client filter if user is a client (strict check)
            $isClient = $user->default_role === UserRole::CLIENT->value
                || $user->default_role === null;

            if ($isClient) {
                $query->whereHas('matter.client', fn ($q) => $q->where('actor_id', $user->id));
            }

            // Handle user_dashboard parameter (trim to handle CHAR column padding)
            if ($request->filled('user_dashboard')) {
                $userDashboard = trim($request->user_dashboard);
                $query->where(fn ($q) => $q
                    ->where('assigned_to', $userDashboard)
                    ->orWhereHas('matter', fn ($mq) => $mq->where('responsible', $userDashboard)));
            }

            return $query;
        };

        $applyFilters($taskQuery);
        $applyFilters($renewalQuery);

        $perPage = config('pagination.tasks', 15);
        $tasks = $taskQuery->simplePaginate($perPage)->appends($request->input());
        $renewals = $renewalQuery->simplePaginate($perPage)->appends($request->input());

        return view('home', compact('categories', 'taskscount', 'tasks', 'renewals'));
    }

    /**
     * Clear selected tasks by setting their done dates.
     *
     * @param  ClearTasksRequest  $request  Contains task_ids array and done_date
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearTasks(ClearTasksRequest $request)
    {
        $tids = $request->task_ids;
        $done_date = Carbon::createFromLocaleIsoFormat('L', app()->getLocale(), $request->done_date);
        $updated = 0;
        foreach ($tids as $id) {
            $task = Task::find($id);
            $task->done_date = $done_date;
            $returncode = $task->save();
            if ($returncode) {
                $updated++;
            }
        }

        return response()->json(['not_updated' => (count($tids) - $updated), 'errors' => '']);
    }
}
