<?php

namespace App\Http\Controllers;

use App\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $table_status = $request->input('table_status', null);
        if ($table_status === null) {
            $tables = Table::all();
        } else {
            $tables = Table::where('table_status', $table_status)->get();
        }

        return response()->json(compact('tables'), 200);
    }
}
