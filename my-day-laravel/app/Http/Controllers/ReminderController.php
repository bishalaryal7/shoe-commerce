<?php

namespace App\Http\Controllers;

use App\Models\CompanionTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tasks = CompanionTask::where('owner', $user->companion_owner)->where('done', false)->whereNotNull('due_date')->whereDate('due_date', '<=', today())->get();
        foreach ($tasks as $task) {
            DB::table('reminder_notifications')->insertOrIgnore(['user_id' => $user->id, 'task_id' => $task->id, 'due_date' => $task->due_date->format('Y-m-d'), 'created_at' => now(), 'updated_at' => now()]);
        }
        $items = DB::table('reminder_notifications as n')->join('companion_tasks as t', 'n.task_id', '=', 't.id')->where('n.user_id', $user->id)->where('t.done', false)->whereColumn('n.due_date', 't.due_date')->orderByDesc('n.id')->limit(50)->get(['n.id', 'n.read_at', 'n.due_date', 't.text']);
        return response()->json(['notifications' => $items])->header('Cache-Control', 'no-store');
    }
    public function read(Request $request, int $id)
    {
        $updated = DB::table('reminder_notifications')->where('user_id', $request->user()->id)->where('id', $id)->update(['read_at' => now(), 'updated_at' => now()]);
        abort_unless($updated, 404); return response()->noContent();
    }
}
