<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Traits\Filterable;
use App\Traits\HandlesAuditFields;
use Illuminate\Http\Request;

/**
 * Manages actor roles in the system.
 *
 * Roles define the type of relationship an actor has with a matter
 * (e.g., Applicant, Inventor, Agent, Contact). Used in matter-actor
 * relationships and default actor configurations.
 */
class RoleController extends Controller
{
    use Filterable, HandlesAuditFields;

    /**
     * Filter rules for index method.
     */
    protected array $filterRules = [];

    public function __construct()
    {
        $this->filterRules = [
            'Code' => fn ($q, $v) => $q->whereLike('code', "$v%"),
            'Name' => fn ($q, $v) => $q->whereJsonLike('name', $v),
        ];
    }
    /**
     * Display a list of roles with filtering.
     *
     * @param Request $request Filter parameters
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Role::query();
        $this->applyFilters($query, $request);
        $roles = $query->get();

        if ($request->wantsJson()) {
            return response()->json($roles);
        }

        return view('role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('role.create');
    }

    /**
     * Store a newly created role.
     *
     * @param StoreRoleRequest $request Validated role data
     * @return Role The created role
     */
    public function store(StoreRoleRequest $request)
    {
        $this->mergeCreator($request);

        return Role::create($this->getFilteredData($request));
    }

    /**
     * Display the specified role.
     *
     * @param Role $role The role to display
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $role->get();

        return view('role.show', compact('role'));
    }

    /**
     * Update the specified role.
     *
     * @param UpdateRoleRequest $request Validated role data
     * @param Role $role The role to update
     * @return Role The updated role
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->mergeUpdater($request);
        $role->update($this->getFilteredData($request));

        return $role;
    }

    /**
     * Remove the specified role from storage.
     *
     * @param Role $role The role to delete
     * @return Role The deleted role
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return $role;
    }
}
