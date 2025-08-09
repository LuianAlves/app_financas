<?php

namespace App\Http\Controllers\Api;

use App\Models\Security;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    /** @var \App\Models\Security */
    public $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    // GET /api/investments
    public function index()
    {
        $securities = $this->security::with('trades')
            ->where('user_id', Auth::id())
            ->get();

        $securities->each(function ($sec) {
            $sec->ticker      = strtoupper((string) $sec->ticker);
            $sec->name        = strtoupper((string) ($sec->name ?? ''));
            $sec->last_price  = brlPrice($sec->last_price);
        });

        return response()->json($securities);
    }

    // POST /api/investments
    public function store(Request $request)
    {
        $data = $request->validate([
            'ticker'     => 'required|string|max:10',
            'name'       => 'nullable|string|max:255',
            'class'      => 'nullable|string|max:50',
            'currency'   => 'nullable|string|max:10',
            'last_price' => 'required|numeric|min:0',
        ]);

        $data['user_id'] = Auth::id();

        $security = $this->security->create($data)->load('trades');

        // formata retorno
        $security->ticker     = strtoupper((string) $security->ticker);
        $security->name       = strtoupper((string) ($security->name ?? ''));
        $security->last_price = brlPrice($security->last_price);

        return response()->json($security, 201);
    }

    // GET /api/investments/{security}
    public function show(Security $security)
    {
        // garante owner
        abort_if($security->user_id !== Auth::id(), 403);

        $security->load('trades');
        return response()->json($security);
    }

    // PUT/PATCH /api/investments/{security}
    public function update(Request $request, Security $security)
    {
        abort_if($security->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'ticker'     => 'sometimes|string|max:10',
            'name'       => 'sometimes|string|max:255',
            'class'      => 'sometimes|string|max:50',
            'currency'   => 'sometimes|string|max:10',
            'last_price' => 'sometimes|numeric|min:0',
        ]);

        $security->update($data);

        // pode formatar como no index se preferir:
        // $security->ticker = strtoupper($security->ticker);
        // $security->name   = strtoupper((string) $security->name);
        // $security->last_price = brlPrice($security->last_price);

        return response()->json($security);
    }

    // DELETE /api/investments/{security}
    public function destroy(Security $security)
    {
        abort_if($security->user_id !== Auth::id(), 403);

        $security->delete();
        return response()->json(null, 204);
    }
}
