<?php

namespace App\Http\Controllers\Internal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Maintenance;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Maintenance::class);

        $page_size = $request->input('page_size', 20);
        $is_active = $request->input('is_active', '');
        $search = $request->input('search', '');
        $order_by = $request->input('order_by', 'description');
        $order = $request->input('order', 'asc');

        $query = Maintenance::query();

        if ($is_active != '') {
            $query = $query->active($is_active);
        }

        if ($search != '') {
            $query = $query->where(function($query) use($search) {
                return $query->where('type', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }

        $query = $query->orderBy($order_by, $order);

        return response()->json($query->paginate($page_size));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Maintenance::class);
        $this->authorize('update', Maintenance::class);

        $this->validate($request, [
            'type'   => 'required',
            'description' => 'required',
        ]);

        $record = new Maintenance($request->all(['type', 'description', 'is_active']));

        if ($record->is_active === true) {
            $this->updateActiveRecord();
        }

        $record->save();

        return response()->json($record);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('update', Maintenance::class);

        $record = Maintenance::find($id);

        $record->type = $request->input('type', $record->type);
        $record->description = $request->input('description', $record->description);
        $record->is_active = $request->input('is_active', $record->is_active);

        if ($record->is_active === true) {
            $this->updateActiveRecord();
        }

        $record->save();

        return response()->json($record);
    }

    public function destroy($id){
        $this->authorize('delete', Maintenance::class);
        $record = Maintenance::query()->findOrFail($id);
        $record->delete();

        return $record;
    }

    public function getActiveRecord() {
        $record = Maintenance::where('is_active', true)->first();
        return response()->json($record);
    }

    public function updateActiveRecord() {
        Maintenance::where('is_active', true)->update(['is_active' => false]);
    }
}
