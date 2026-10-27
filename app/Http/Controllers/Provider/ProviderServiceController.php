<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreServiceRequest;
use App\Http\Requests\Provider\UpdateServiceRequest;
use App\Models\Provider;
use App\Models\Service;
use App\Services\ProviderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderServiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProviderService $providerService
    ) {}

    /**
     * List the provider's service catalog.
     */
    public function index(Request $request): View
    {
        $provider = $this->currentProvider($request);

        $services = $provider->services()->latest()->get();

        return view('provider.services.index', compact('provider', 'services'));
    }

    /**
     * Show the create service form.
     */
    public function create(Request $request): View
    {
        $provider = $this->currentProvider($request);
        $service = new Service(['tags' => [], 'specs' => []]);

        return view('provider.services.form', compact('provider', 'service'));
    }

    /**
     * Store a new catalog service.
     */
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $provider = $this->currentProvider($request);

        $this->providerService->createService($provider, $request->validated(), $request->file('image'));

        return redirect()->route('provider.services.index')
            ->with('success', __('Service added to your catalog.'));
    }

    /**
     * Show the edit service form.
     */
    public function edit(Request $request, Service $service): View
    {
        $provider = $this->currentProvider($request);
        $this->authorize('manage', $service);

        return view('provider.services.form', compact('provider', 'service'));
    }

    /**
     * Update an existing catalog service.
     */
    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->providerService->updateService(
            $service,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return redirect()->route('provider.services.index')
            ->with('success', __('Service updated successfully.'));
    }

    /**
     * Remove a catalog service.
     */
    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);

        $this->providerService->deleteService($service);

        return redirect()->route('provider.services.index')
            ->with('success', __('Service removed from your catalog.'));
    }

    protected function currentProvider(Request $request): Provider
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            throw new HttpResponseException(
                redirect()->route('provider.register')->with('info', __('Please register as a service provider first.'))
            );
        }

        return $provider;
    }
}
