<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Niche;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminNicheController extends Controller
{
    public function index()
    {
        $niches = Niche::orderBy('sort_order')->orderBy('name')->paginate(50);

        return view('admin.niches.index', compact('niches'));
    }

    public function create()
    {
        $niche = new Niche([
            'color_theme' => 'blue',
            'tone'        => 'professional',
            'is_active'   => true,
            'sort_order'  => (int) (Niche::max('sort_order') ?? 0) + 10,
            'benefits'    => [],
            'demo_messages' => [],
            'faq'         => [],
        ]);

        return view('admin.niches.form', [
            'niche'    => $niche,
            'isEdit'   => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $niche = Niche::create($data);

        return redirect()
            ->route('admin.niches.index')
            ->with('success', "Nișa „{$niche->name}” a fost creată.");
    }

    public function edit(Niche $niche)
    {
        return view('admin.niches.form', [
            'niche'    => $niche,
            'isEdit'   => true,
        ]);
    }

    public function update(Request $request, Niche $niche)
    {
        $data = $this->validated($request, $niche->id);
        $niche->update($data);

        return redirect()
            ->route('admin.niches.index')
            ->with('success', "Nișa „{$niche->name}” a fost actualizată.");
    }

    public function destroy(Niche $niche)
    {
        $name = $niche->name;
        $niche->delete();

        return redirect()
            ->route('admin.niches.index')
            ->with('success', "Nișa „{$name}” a fost ștearsă.");
    }

    public function toggle(Niche $niche): JsonResponse
    {
        $niche->is_active = ! $niche->is_active;
        $niche->save();

        return response()->json([
            'ok'        => true,
            'is_active' => $niche->is_active,
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            return response()->json(['ok' => false, 'error' => 'ids must be an array'], 422);
        }

        foreach ($ids as $index => $id) {
            Niche::where('id', (int) $id)->update(['sort_order' => ($index + 1) * 10]);
        }

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $rules = [
            'slug'                    => 'required|string|max:120|alpha_dash|unique:niches,slug' . ($id ? ",{$id}" : ''),
            'name'                    => 'required|string|max:200',
            'vertical_label'          => 'required|string|max:80',
            'color_theme'             => 'required|in:red,emerald,blue,amber,rose,purple,indigo,teal,cyan,orange',
            'tone'                    => 'required|in:formal,professional,friendly,playful',
            'hero_eyebrow'            => 'nullable|string|max:120',
            'hero_title'              => 'required|string|max:200',
            'hero_subtitle'           => 'required|string|max:600',
            'problem_title'           => 'required|string|max:200',
            'problem_text'            => 'required|string|max:3000',
            'solution_title'          => 'required|string|max:200',
            'solution_text'           => 'required|string|max:3000',
            'benefits'                => 'nullable|array',
            'benefits.*.title'        => 'required_with:benefits.*|string|max:200',
            'benefits.*.description'  => 'required_with:benefits.*|string|max:600',
            'demo_messages'           => 'nullable|array',
            'demo_messages.*.role'    => 'required_with:demo_messages.*|in:bot,user',
            'demo_messages.*.text'    => 'required_with:demo_messages.*|string|max:1000',
            'social_proof_text'       => 'nullable|string|max:1500',
            'cta_primary_text'        => 'nullable|string|max:60',
            'cta_primary_href'        => 'nullable|string|max:200',
            'cta_secondary_text'      => 'nullable|string|max:60',
            'cta_secondary_href'      => 'nullable|string|max:200',
            'image_path'              => 'nullable|string|max:300',
            'icon_svg'                => 'nullable|string|max:5000',
            'faq'                     => 'nullable|array',
            'faq.*.question'          => 'required_with:faq.*|string|max:300',
            'faq.*.answer'            => 'required_with:faq.*|string|max:2000',
            'meta_title'              => 'nullable|string|max:200',
            'meta_description'        => 'nullable|string|max:300',
            'sort_order'              => 'nullable|integer',
            'is_active'               => 'boolean',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['slug'] = Str::slug($data['slug']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        // Re-index repeater arrays and drop empty rows
        foreach (['benefits', 'demo_messages', 'faq'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = array_values(array_filter($data[$key], fn($row) => is_array($row) && array_filter($row, fn($v) => $v !== null && $v !== '')));
            } else {
                $data[$key] = [];
            }
        }

        return $data;
    }
}
