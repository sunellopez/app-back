<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;


class ExpenseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return Expense::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(10);
    }

    public function summary()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 🗓️ Rango de la SEMANA PASADA COMPLETA
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();
        
        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        $total = $expenses->sum('amount');

        return response()->json([
            'data' => [
                'total' => $total,
                'start' => $startOfWeek->toDateString(),
                'end' => $endOfWeek->toDateString(),
                'count' => $expenses->count()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $expense = Expense::create([
            'user_id' => $user->id,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
        ]);

        return response()->json([
            'message' => 'Gasto creado correctamente.',
            'data' => $expense,
        ], 201);
    }
}