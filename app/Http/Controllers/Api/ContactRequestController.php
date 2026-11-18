<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\Request;

class ContactRequestController extends Controller
{
    // Formulário de orçamento da landing page (público, sem login). O campo
    // "website" é uma isca pra robôs: humano nunca vê nem preenche — se vier
    // preenchido, finge sucesso e não grava nada.
    public function store(Request $request)
    {
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Recebemos seu pedido!'], 201);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'duration_hours' => ['nullable', 'integer', 'in:2,3,4'],
            'games' => ['nullable', 'array', 'max:20'],
            'games.*' => ['string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        ContactRequest::create($data);

        return response()->json(['message' => 'Recebemos seu pedido! Em breve entraremos em contato.'], 201);
    }

    // Painel (só admin): lista os pedidos de orçamento, do mais novo pro mais antigo.
    public function index()
    {
        return response()->json([
            'data' => ContactRequest::latest()->take(500)->get(),
        ]);
    }
}
