<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\SupportRequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    // GET /support
    public function index()
    {
        // categorias fixas – você pode mexer aqui
        $categories = [
            [
                'slug' => 'conta',
                'name' => 'Conta',
                'description' => 'Como cadastrar, editar e excluir contas.',
            ],
            [
                'slug' => 'cofrinho',
                'name' => 'Cofrinho',
                'description' => 'Como usar os cofrinhos e metas.',
            ],
            [
                'slug' => 'cartao',
                'name' => 'Cartão',
                'description' => 'Cartões, limites e faturas.',
            ],
            [
                'slug' => 'perfil',
                'name' => 'Perfil',
                'description' => 'Dados do usuário, segurança, etc.',
            ],
            [
                'slug' => 'outros',
                'name' => 'Outros',
                'description' => 'Não encontrou o que precisa? Fale com a gente.',
            ],
        ];

        return view('app.support.support_index', compact('categories'));
    }

    // GET /support/{slug}
    public function article(string $slug)
    {
        // aqui só escolhemos qual view abrir
        switch ($slug) {
            case 'conta':
                return view('app.support.articles.account');

            case 'cofrinho':
                return view('app.support.articles.saving');

            case 'cartao':
                return view('app.support.articles.card');

            case 'perfil':
                return view('app.support.articles.profile');

            case 'outros':
                return view('app.support.articles.outros');

            default:
                abort(404);
        }
    }

    // POST /support/outros
    public function storeOther(Request $request)
    {
        $validated = $request->validate([
            'message'      => ['required', 'string', 'max:5000'],
            'subject'      => ['nullable', 'string', 'max:255'],
            'images'       => ['nullable', 'array', 'max:5'], // até 5 imagens, ajuste se quiser
            'images.*'     => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'], // 4MB cada
        ]);

        // cria o registro principal
        $supportRequest = SupportRequest::create([
            'user_id'       => auth()->id(),
            'category_slug' => 'outros',
            'subject'       => $validated['subject'] ?? null,
            'message'       => $validated['message'],
            'status'        => 'aberto',
        ]);

        // salva anexos (se houver)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('support_attachments', 'public'); // storage/app/public/support_attachments

                SupportRequestAttachment::create([
                    'support_request_id' => $supportRequest->id,
                    'path'               => $path,
                    'original_name'      => $file->getClientOriginalName(),
                    'mime_type'          => $file->getClientMimeType(),
                    'size'               => (int) round($file->getSize() / 1024),
                ]);
            }
        }

        return back()->with('success', 'Sua mensagem foi enviada. Em breve nossa equipe vai analisar.');
    }
}
