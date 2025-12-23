<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin');
    }

    /**
     * Display the country management interface.
     */
    public function index(Request $request)
    {
        $iso = $request->input('iso');
        $name = $request->input('name');
        $query = Country::query();

        if (! is_null($iso)) {
            $query = $query->where('iso', 'LIKE', $iso.'%');
        }

        if (! is_null($name)) {
            $driver = DB::connection()->getDriverName();
            $isPostgres = $driver === 'pgsql';

            if ($isPostgres) {
                $query = $query->where(function ($subQuery) use ($name) {
                    $subQuery->whereRaw("name ->> 'en' ILIKE ?", ['%'.$name.'%'])
                        ->orWhereRaw("name ->> 'fr' ILIKE ?", ['%'.$name.'%'])
                        ->orWhereRaw("name ->> 'de' ILIKE ?", ['%'.$name.'%']);
                });
            } else {
                $query = $query->where(function ($subQuery) use ($name) {
                    $subQuery->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE LOWER(?)", ['%'.$name.'%'])
                        ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr'))) LIKE LOWER(?)", ['%'.$name.'%'])
                        ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.de'))) LIKE LOWER(?)", ['%'.$name.'%']);
                });
            }
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $countries = $query->orderBy('iso')->paginate(21);
        $countries->appends($request->input())->links();

        return view('countries.index', compact('countries'));
    }

    /**
     * Show the form for creating a new country.
     */
    public function create()
    {
        $country = new Country;

        return view('countries.create', compact('country'));
    }

    /**
     * Store a newly created country.
     */
    public function store(StoreCountryRequest $request)
    {
        $validated = $request->validated();

        // Create country with basic data first
        $countryData = [
            'iso' => $validated['iso'],
            'numcode' => 0, // Mark as custom country
            'ep' => $validated['ep'] ?? 0,
            'wo' => $validated['wo'] ?? 0,
            'em' => $validated['em'] ?? 0,
            'oa' => $validated['oa'] ?? 0,
            'renewal_first' => $validated['renewal_first'],
            'renewal_base' => $validated['renewal_base'],
            'renewal_start' => $validated['renewal_start'],
        ];

        $country = new Country($countryData);

        // Set translations using spatie methods
        foreach ($validated['name'] as $locale => $name) {
            if (! empty($name)) {
                $country->setTranslation('name', $locale, $name);
            }
        }

        $country->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Country created successfully'),
            'country' => $country,
        ]);
    }

    /**
     * Display the specified country.
     */
    public function show(Request $request, Country $country)
    {
        // If a locale is requested, temporarily set the app locale
        if ($request->has('locale')) {
            app()->setLocale($request->input('locale'));
        }

        return view('countries.show', compact('country'));
    }

    /**
     * Update the specified country.
     */
    public function update(UpdateCountryRequest $request, Country $country)
    {
        // Prevent editing for standard countries (numcode > 0)
        if ($country->numcode > 0) {
            if ($request->has('name') || $request->has('iso')) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Cannot modify name or ISO code for standard countries'),
                ], 422);
            }
        }

        // If updating a name translation
        if ($request->has('name')) {
            if (is_array($request->name)) {
                // Handle individual language updates using spatie translatable methods
                foreach ($request->name as $locale => $value) {
                    $country->setTranslation('name', $locale, $value);
                }
                $country->save();

                return response()->json([
                    'status' => 'success',
                    'message' => __('Country updated successfully'),
                    'country' => $country,
                ]);
            } elseif (! json_decode($request->name)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Invalid JSON format for name field'),
                ], 422);
            }
        }

        // For regular field updates
        $country->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => __('Country updated successfully'),
            'country' => $country,
        ]);
    }

    /**
     * Remove the specified country.
     */
    public function destroy(Country $country)
    {
        // Prevent deletion of standard countries (numcode > 0)
        if ($country->numcode > 0) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete standard countries'),
            ], 422);
        }

        $country->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Country deleted successfully'),
        ]);
    }
}
