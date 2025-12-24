<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateRuleRequest;
use App\Models\Rule;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manages task generation rules for matter workflows.
 *
 * Rules define automatic task creation based on trigger events, calculating
 * due dates using configurable offsets and conditions based on matter properties
 * like country, category, and type.
 */
class RuleController extends Controller
{
    use HandlesAuditFields;
    use Filterable;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Task' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('taskInfo', fn ($tq) => $tq->whereJsonLike('name', $escapedValue));
            },
            'Trigger' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('trigger', fn ($tq) => $tq->whereJsonLike('name', $escapedValue));
            },
            'Country' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereLike('for_country', $escapedValue.'%');
            },
            'Category' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('category', fn ($cq) => $cq->whereJsonLike('category', $escapedValue));
            },
            'Detail' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereJsonLike('detail', $escapedValue);
            },
            'Type' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereHas('type', fn ($tq) => $tq->whereJsonLike('type', $escapedValue));
            },
            'Origin' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereLike('for_origin', "{$escapedValue}%");
            },
        ];
    }
    /**
     * Display a paginated list of rules with filtering.
     *
     * @param Request $request Filter parameters for rules
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Rule::class);

        // Validate input
        $request->validate([
            'Task' => 'nullable|string|max:255',
            'Trigger' => 'nullable|string|max:255',
            'Country' => 'nullable|string|max:255',
            'Category' => 'nullable|string|max:255',
            'Detail' => 'nullable|string|max:255',
            'Type' => 'nullable|string|max:255',
            'Origin' => 'nullable|string|max:255',
        ]);

        $rule = Rule::query();
        $locale = app()->getLocale();
        // Normalize to the base locale (e.g., 'en' from 'en_US')
        // Validate locale is only alphabetic characters to prevent SQL injection
        $baseLocale = substr($locale, 0, 2);
        if (! preg_match('/^[a-zA-Z]{2}$/', $baseLocale)) {
            $baseLocale = 'en'; // Fallback to English if invalid
        }

        $this->applyFilters($rule, $request);

        // Database-agnostic JSON ordering
        $driver = DB::connection()->getDriverName();
        $isPostgres = $driver === 'pgsql';
        $orderExpr = $isPostgres
            ? "t.name ->> '".addslashes($baseLocale)."'"
            : "JSON_UNQUOTE(JSON_EXTRACT(t.name, '$.\"".addslashes($baseLocale)."\"'))";

        $query = $rule->with(['country:iso,name', 'trigger:code,name', 'category:code,category', 'origin:iso,name', 'type:code,type', 'taskInfo:code,name'])
            ->select('task_rules.*')
            ->join('event_name AS t', 't.code', '=', 'task_rules.task')
            ->orderByRaw($orderExpr);

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $ruleslist = $query->paginate(21);
        $ruleslist->appends($request->input())->links();

        return view('rule.index', compact('ruleslist'));
    }

    /**
     * Display the specified rule.
     *
     * @param Rule $rule The rule to display
     * @return \Illuminate\Http\Response
     */
    public function show(Rule $rule)
    {
        $this->authorize('view', $rule);

        $ruleInfo = $rule->load([
            'trigger:code,name',
            'country:iso,name',
            'category:code,category',
            'origin:iso,name',
            'type:code,type',
            'taskInfo:code,name',
            'condition_eventInfo:code,name',
            'abort_onInfo:code,name',
            'responsibleInfo:login,name',
        ]);

        $ruleComments = $rule->getTableComments();

        return view('rule.show', compact('ruleInfo', 'ruleComments'));
    }

    /**
     * Show the form for creating a new rule.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Rule::class);

        $rule = new Rule;
        $ruleComments = $rule->getTableComments();

        return view('rule.create', compact('ruleComments'));
    }

    /**
     * Update the specified rule.
     *
     * @param UpdateRuleRequest $request Validated rule data
     * @param Rule $rule The rule to update
     * @return Rule The updated rule
     */
    public function update(UpdateRuleRequest $request, Rule $rule)
    {
        $this->mergeUpdater($request);
        $rule->update($this->getFilteredData($request));

        return $rule;
    }

    /**
     * Store a newly created rule.
     *
     * @param StoreRuleRequest $request Validated rule data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRuleRequest $request)
    {
        $this->mergeCreator($request);
        Rule::create($this->getFilteredData($request));

        return response()->json(['redirect' => route('rule.index')]);
    }

    /**
     * Remove the specified rule from storage.
     *
     * @param Rule $rule The rule to delete
     * @return Rule The deleted rule
     */
    public function destroy(Rule $rule)
    {
        $this->authorize('delete', $rule);

        $rule->delete();

        return $rule;
    }
}
