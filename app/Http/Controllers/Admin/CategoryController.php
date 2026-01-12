<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\CategoriesChanged;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderByDesc('created_at')->paginate(20);

        // Count Admins and Agents per category for current page
        $ids = $categories->pluck('id')->filter()->values();
        $roleCounts = \App\Models\User::query()
            ->whereIn('category_id', $ids)
            ->whereIn('role', ['admin', 'agent'])
            ->selectRaw('category_id, role, COUNT(*) as total')
            ->groupBy('category_id', 'role')
            ->get()
            ->groupBy('category_id');

        foreach ($categories as $c) {
            $group = $roleCounts[$c->id] ?? collect();
            $adminCount = (int) ($group->firstWhere('role', 'admin')->total ?? 0);
            $agentCount = (int) ($group->firstWhere('role', 'agent')->total ?? 0);
            $c->setAttribute('admins_count', $adminCount);
            $c->setAttribute('agents_count', $agentCount);
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $cat = Category::create($data);

        event(new CategoriesChanged(categoryId: (int) $cat->id, action: 'created'));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dibuat');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        event(new CategoriesChanged(categoryId: (int) $category->id, action: 'updated'));

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Category $category)
    {
        $id = (int) $category->id;
        $category->delete();
        event(new CategoriesChanged(categoryId: $id, action: 'deleted'));
        return redirect()->route('admin.categories.index')->with('deleted_success', 'Kategori berhasil dihapus');
    }

    public function exportCsv(Request $request)
    {
        $rows = Category::orderByDesc('created_at')->get();
        $ids = $rows->pluck('id')->values();
        $roleCounts = \App\Models\User::query()
            ->whereIn('category_id', $ids)
            ->whereIn('role', ['admin', 'agent'])
            ->selectRaw('category_id, role, COUNT(*) as total')
            ->groupBy('category_id', 'role')
            ->get()
            ->groupBy('category_id');

        $lines = [];
        $lines[] = '"No","Nama","Slug","Deskripsi","Jumlah Admin","Jumlah Teknisi"';
        $i = 1;
        foreach ($rows as $c) {
            $group = $roleCounts[$c->id] ?? collect();
            $adminCount = (int) ($group->firstWhere('role', 'admin')->total ?? 0);
            $agentCount = (int) ($group->firstWhere('role', 'agent')->total ?? 0);
            $line = [
                $i++,
                $c->name,
                $c->slug,
                $c->description,
                $adminCount,
                $agentCount,
            ];
            $lines[] = implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $line));
        }

        $csv = implode("\r\n", $lines) . "\r\n";
        $file = 'categories_' . date('Ymd_His') . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $file . '"'
        ]);
    }

    public function exportPdf(Request $request)
    {
        $categories = Category::orderByDesc('created_at')->get();
        $ids = $categories->pluck('id')->values();
        $roleCounts = \App\Models\User::query()
            ->whereIn('category_id', $ids)
            ->whereIn('role', ['admin', 'agent'])
            ->selectRaw('category_id, role, COUNT(*) as total')
            ->groupBy('category_id', 'role')
            ->get()
            ->groupBy('category_id');

        foreach ($categories as $c) {
            $group = $roleCounts[$c->id] ?? collect();
            $c->setAttribute('admins_count', (int) ($group->firstWhere('role', 'admin')->total ?? 0));
            $c->setAttribute('agents_count', (int) ($group->firstWhere('role', 'agent')->total ?? 0));
        }

        return view('admin.categories.export', compact('categories'));
    }
}
