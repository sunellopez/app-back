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

    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now(); 
        $startOfWeek = $now->copy()->startOfWeek(); 

        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $now->toDateString()])
            ->get();

        $total = $expenses->sum('amount');

        return response()->json([
            'total' => $total,
            'start' => $startOfWeek->toDateString(),
            'end' => $now->toDateString(),
            'count' => $expenses->count(),
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