<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages matter categories.
 *
 * Categories classify matters into types like Patent, Trademark, Design, etc.
 * Controls matter display grouping and reference number prefixes.
 */
class CategoryController extends Controller
{
    use Filterable;
    use HandlesAuditFields;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Code' => fn ($q, $v) => $q->whereLike('code', "$v%"),
            'Category' => fn ($q, $v) => $q->whereJsonLike('category', $v),
        ];
    }

    /**
     * Display a list of categories with filtering.
     *
     * @param  Request  $request  Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();
        $this->applyFilters($query, $request);
        $categories = $query->get();

        if ($request->wantsJson()) {
            return response()->json($categories);
        }

        return view('category.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Category::class);

        return view('category.create');
    }

    /**
     * Store a newly created category.
     *
     * @param  StoreCategoryRequest  $request  Validated category data
     * @return Category The created category
     */
    public function store(StoreCategoryRequest $request)
    {
        $this->mergeCreator($request);

        return Category::create($this->getFilteredData($request));
    }

    /**
     * Display the specified category.
     *
     * @param  Category  $category  The category to display
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        $this->authorize('view', $category);

        $category->load(['displayWithInfo:code,category']);

        return view('category.show', compact('category'));
    }

    /**
     * Update the specified category.
     *
     * @param  UpdateCategoryRequest  $request  Validated category data
     * @param  Category  $category  The category to update
     * @return Category The updated category
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->mergeUpdater($request);
        $category->update($this->getFilteredData($request));

        return $category;
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  Category  $category  The category to delete
     * @return Category The deleted category
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return $category;
    }
}
