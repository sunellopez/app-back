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

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // 📊 Agrupa por día de la semana
        $expensesByDay = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->selectRaw('DAYOFWEEK(date) as day_of_week, SUM(amount) as total')
            ->groupBy('day_of_week')
            ->get();
        
        // 🗓️ Genera array con Lunes a Domingo
        $weekDays = [
            1 => 'Domingo',
            2 => 'Lunes',
            3 => 'Martes',
            4 => 'Miércoles',
            5 => 'Jueves',
            6 => 'Viernes',
            7 => 'Sábado'
        ];

        $dailyTotals = [
            'Lunes' => 0,
            'Martes' => 0,
            'Miércoles' => 0,
            'Jueves' => 0,
            'Viernes' => 0,
            'Sábado' => 0,
            'Domingo' => 0,
        ];

        foreach ($expensesByDay as $day) {
            $dayName = $weekDays[$day->day_of_week];
            $dailyTotals[$dayName] = $day->total;
        }

        return response()->json([
            'data' => [
                'total' => array_sum($dailyTotals),
                'daily' => array_values($dailyTotals),
                'labels' => array_keys($dailyTotals),
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

    public function getHighestExpenseThisWeek() 
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $expense = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->orderByDesc('amount')
            ->first();
        
        return response()->json([
            'data' => $expense
        ]);
    }

    public function getMonthlyExpenses(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 📅 Obtiene el año o usa el actual
        $year = $request->input('year', Carbon::now()->year);

        // 🧮 Agrupa por mes y suma
        $expenses = Expense::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 🎯 Prepara array con 12 posiciones (1-12)
        $monthlyTotals = array_fill(1, 12, 0);

        foreach ($expenses as $expense) {
            $monthlyTotals[$expense->month] = (float) $expense->total;
        }

        // 📌 Reindexa a 0-11
        $monthlyTotals = array_values($monthlyTotals);

        return response()->json([
            'data' => [
                'year' => $year,
                'monthlyExpenses' => $monthlyTotals,
                'months' => ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
            ]
        ]);
    }
}