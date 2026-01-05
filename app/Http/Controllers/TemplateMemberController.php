<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateMemberRequest;
use App\Http\Requests\UpdateTemplateMemberRequest;
use App\Models\TemplateMember;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages template members (individual document templates).
 *
 * Template members are specific document instances within a template class,
 * containing Blade template markup for generating correspondence in different
 * languages and formats.
 */
class TemplateMemberController extends Controller
{
    use HandlesAuditFields;

    /**
     * Supported template languages.
     *
     * @var array
     */
    public $languages = ['fr' => 'Français',
        'en' => 'English',
        'de' => 'Deutsch'];

    /**
     * Display a paginated list of template members with filtering.
     *
     * @param  Request  $request  Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TemplateMember::class);

        $Summary = $request->summary;
        $Style = $request->style;
        $Language = $request->language;
        $Class = $request->class;
        $Format = $request->format;
        $Category = $request->category;
        $template_members = TemplateMember::query();
        if (! is_null($Summary)) {
            $template_members = $template_members->where('summary', 'LIKE', "%$Summary%");
        }
        if (! is_null($Category)) {
            $template_members = $template_members->where('category', 'LIKE', "$Category%");
        }
        if (! is_null($Language)) {
            $template_members = $template_members->where('language', 'LIKE', "$Language%");
        }
        if (! is_null($Class)) {
            $template_members = $template_members->whereHas('class', function ($query) use ($Class) {
                $query->where('name', 'LIKE', "$Class%");
            });
        }
        if (! is_null($Format)) {
            $template_members = $template_members->where('format', 'like', $Format.'%');
        }
        if (! is_null($Style)) {
            $template_members = $template_members->where('style', 'LIKE', "$Style%");
        }

        $query = $template_members->orderBy('summary');

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $template_members = $query->simplePaginate(config('renewal.general.paginate') == 0 ? 25 : intval(config('renewal.general.paginate')));
        $template_members->appends($request->input())->links();

        return view('template-members.index', compact('template_members'));
    }

    /**
     * Show the form for creating a new template member.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', TemplateMember::class);

        $table = new TemplateMember;
        $tableComments = $table->getTableComments();
        $languages = $this->languages;

        return view('template-members.create', compact('tableComments', 'languages'));
    }

    /**
     * Store a newly created template member.
     *
     * @param  StoreTemplateMemberRequest  $request  Validated template member data
     * @return TemplateMember The created template member
     */
    public function store(StoreTemplateMemberRequest $request)
    {
        $this->mergeCreator($request);

        return TemplateMember::create($this->getFilteredData($request));
    }

    /**
     * Display the specified template member.
     *
     * @param  TemplateMember  $templateMember  The template member to display
     * @return \Illuminate\Http\Response
     */
    public function show(TemplateMember $templateMember)
    {
        $this->authorize('view', $templateMember);

        $tableComments = $templateMember->getTableComments();
        $templateMember->with(['class', 'style', 'language']);
        $languages = $this->languages;

        return view('template-members.show', compact('templateMember', 'languages', 'tableComments'));
    }

    /**
     * Update the specified template member.
     *
     * @param  UpdateTemplateMemberRequest  $request  Validated template member data
     * @param  TemplateMember  $templateMember  The template member to update
     * @return TemplateMember The updated template member
     */
    public function update(UpdateTemplateMemberRequest $request, TemplateMember $templateMember)
    {
        $this->mergeUpdater($request);
        $templateMember->update($this->getFilteredData($request));

        return $templateMember;
    }

    /**
     * Remove the specified template member from storage.
     *
     * @param  TemplateMember  $templateMember  The template member to delete
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TemplateMember $templateMember)
    {
        $this->authorize('delete', $templateMember);

        $templateMember->delete();

        return response()->json(['success' => 'Template deleted']);
    }
}
