<?php

namespace App\Http\Controllers;

use App\Enums\EventCode;
use App\Enums\UserRole;
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
     * @param Request $request
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

        // Apply client filter if user is a client
        if (Auth::user()->default_role == UserRole::CLIENT->value || empty(Auth::user()->default_role)) {
            $taskQuery->whereHas('matter.client', fn ($q) => $q->where('actor_id', Auth::user()->id));
            $renewalQuery->whereHas('matter.client', fn ($q) => $q->where('actor_id', Auth::user()->id));
        }

        // Handle user_dashboard parameter
        if ($request->user_dashboard) {
            $userDashboard = $request->user_dashboard;
            $taskQuery->where(fn ($query) => $query
                ->where('assigned_to', $userDashboard)
                ->orWhereHas('matter', fn ($q) => $q->where('responsible', $userDashboard)));
            $renewalQuery->where(fn ($query) => $query
                ->where('assigned_to', $userDashboard)
                ->orWhereHas('matter', fn ($q) => $q->where('responsible', $userDashboard)));
        }

        $tasks = $taskQuery->simplePaginate(config('pagination.tasks'))->appends($request->input());
        $renewals = $renewalQuery->simplePaginate(config('pagination.tasks'))->appends($request->input());

        return view('home', compact('categories', 'taskscount', 'tasks', 'renewals'));
    }

    /**
     * Clear selected tasks by setting their done dates.
     *
     * @param Request $request Contains task_ids array and done_date
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearTasks(Request $request)
    {
        $this->validate($request, [
            'done_date' => 'bail|required',
        ]);
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
