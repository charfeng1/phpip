<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Actor;
use App\Models\Event;
use App\Models\Matter;
use App\Models\MatterActors;
use App\Models\Task;
use App\Models\TemplateClass;
use App\Models\TemplateMember;
use App\Services\DocumentFilterService;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

/**
 * Manages document templates and email generation.
 *
 * Handles template classes and members for generating correspondence.
 * Supports Blade templating for dynamic content generation and mailto
 * link creation for client communications.
 */
class DocumentController extends Controller
{
    use Filterable;
    use HandlesAuditFields;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct(
        protected DocumentFilterService $filterService,
    ) {
        $this->filterRules = [
            'Name' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereLike('name', $escapedValue.'%');
            },
            'Notes' => function ($q, $v) {
                // Escape LIKE wildcards to prevent SQL wildcard injection
                $escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);

                return $q->whereLike('notes', $escapedValue.'%');
            },
        ];
    }

    /**
     * Display a paginated list of template classes with filtering.
     *
     * @param  Request  $request  Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validate input
        $request->validate([
            'Name' => 'nullable|string|max:255',
            'Notes' => 'nullable|string|max:255',
        ]);

        $query = TemplateClass::query();
        $this->applyFilters($query, $request);
        $query = $query->orderBy('name');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $template_classes = $query->simplePaginate(config('renewal.general.paginate') == 0 ? 25 : intval(config('renewal.general.paginate')));
        $template_classes->appends($request->input())->links();

        return view('documents.index', compact('template_classes'));
    }

    /**
     * Show the form for creating a new template class.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $table = new TemplateClass;
        $tableComments = $table->getTableComments();

        return view('documents.create', compact('tableComments'));
    }

    /**
     * Store a newly created template class.
     *
     * @param  StoreDocumentRequest  $request  Validated template class data
     * @return TemplateClass The created template class
     */
    public function store(StoreDocumentRequest $request)
    {
        $this->mergeCreator($request);

        return TemplateClass::create($this->getFilteredData($request));
    }

    /**
     * Display the specified template class.
     *
     * @param  TemplateClass  $class  The template class to display
     * @return \Illuminate\Http\Response
     */
    public function show(TemplateClass $class)
    {
        $tableComments = $class->getTableComments();
        $class->with(['role']);

        return view('documents.show', compact('class', 'tableComments'));
    }

    /**
     * Update the specified template class.
     *
     * @param  UpdateDocumentRequest  $request  Validated template class data
     * @param  TemplateClass  $class  The template class to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDocumentRequest $request, TemplateClass $class)
    {
        $this->mergeUpdater($request);
        $class->update($this->getFilteredData($request));

        return response()->json(['success' => 'Template class updated']);
    }

    /**
     * Remove the specified template class from storage.
     *
     * @param  TemplateClass  $class  The template class to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TemplateClass $class)
    {
        $class->delete();

        return response()->json(['success' => 'Template class deleted']);
    }

    /**
     * Select template members for a matter with filtering.
     *
     * @param  Matter  $matter  The matter to generate correspondence for
     * @param  Request  $request  Filter and context parameters
     * @return \Illuminate\Http\Response
     */
    public function select(Matter $matter, Request $request)
    {
        $template_id = $request->input('template_id');
        // limit to actors with email
        $contacts = MatterActors::where([['matter_id', $matter->id], ['role_code', 'CNT']])->whereNotNull('email');
        if ($contacts->count() === 0) {
            $contacts = MatterActors::select('actor_id', 'name', 'display_name', 'first_name')
                ->where([['matter_id', $matter->id]])->whereNotNull('email')->distinct();
        }
        $contacts = $contacts->get();

        $members = new TemplateMember;
        $tableComments = $members->getTableComments();
        $filters = $request->except(['page']);

        // Use DocumentFilterService to handle filtering logic
        $result = $this->filterService->filterTemplateMembers($members, $filters);

        $members = $result['query']->get();

        return view($result['view'], array_merge([
            'matter' => $matter,
            'members' => $members,
            'contacts' => $contacts,
            'tableComments' => $tableComments,
        ], array_filter([
            'oldfilters' => $result['oldfilters'],
            'event' => $result['event'],
            'task' => $result['task'],
        ])));
    }

    /**
     * Prepare a mailto link with template and matter data.
     *
     * Generates email content from Blade templates using matter, event, and task data.
     * Creates mailto URL with populated subject, body, recipients, and CC.
     *
     * @param  TemplateMember  $member  The template member to use
     * @param  Request  $request  Contains matter_id, event_id, task_id, and recipient selections
     * @return \Illuminate\Http\JsonResponse
     */
    public function mailto(TemplateMember $member, Request $request)
    {
        // Todo Add field for maually add an address
        $data = [];
        $subject = Blade::compileString($member->subject);
        $blade = Blade::compileString($member->body);

        // Get contacts list
        $sendto_ids = [];
        $cc_ids = [];
        foreach ($request->except(['page']) as $attribute => $value) {
            if (str_starts_with($attribute, 'sendto')) {
                $sendto_ids[] = substr($attribute, 7);
            }
            if (str_starts_with($attribute, 'ccto')) {
                $cc_ids[] = substr($attribute, 5);
            }
        }
        if (count($sendto_ids) != 0) {
            $mailto = 'mailto:'.implode(',', Actor::whereIn('id', $sendto_ids)->pluck('email')->all());
            $sep = '?';
            $matter = Matter::find($request->matter_id);
            $event = Event::find($request->event_id);
            $task = Task::find($request->task_id);
            $description = implode("\n", $matter->getDescription($member->language));
            if (count($cc_ids) != 0) {
                $mailto .= $sep.'cc='.implode(',', Actor::whereIn('id', $cc_ids)->pluck('email')->all());
                $sep = '&';
            }
            if ($member->subject != '') {
                $content = $this->renderTemplate($subject, compact('description', 'matter', 'event', 'task'));
                if (is_array($content)) {
                    if (array_key_exists('error', $content)) {
                        return $content;
                    }
                } else {
                    $mailto .= $sep.'subject='.rawurlencode($content);
                    $sep = '&';
                }
            }
            $content = $this->renderTemplate($blade, compact('description', 'matter', 'event', 'task'));
            if (is_array($content)) {
                if (array_key_exists('error', $content)) {
                    return $content;
                }
            } else {
                if ($member->format == 'HTML') {
                    $mailto .= $sep.'html-body='.rawurlencode($content);
                } else {
                    $mailto .= $sep.'body='.rawurlencode($content);
                }

                return json_encode(['mailto' => $mailto]);
            }
        } else {
            return json_encode(['message' => 'You need to select at least one contact.']);
        }
    }

    /**
     * Render a Blade template with provided data.
     *
     * @param  string  $template  Compiled Blade template string
     * @param  array  $data  Data to pass to the template
     * @return string|array Rendered content or error array
     */
    private function renderTemplate(string $template, array $data)
    {
        try {
            return Blade::render($template, $data);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
