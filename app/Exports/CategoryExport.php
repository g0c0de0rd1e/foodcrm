<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryExport extends BaseExport implements FromCollection, WithHeadings
{
    protected string $language;

    public function __construct(string $language) {
        $this->language = $language;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $categories = Category::with([
            'translation' => fn($q) => $q->where('locale', $this->language)
                ->orWhere('locale', data_get($language, 'locale')),
        ])
            ->where('type', Category::MAIN)
            ->orderBy('id')
            ->get();

        return $categories->map(fn(Category $category) => $this->tableBody($category));
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            '#',
            'Uu Id',
            'Keywords',
            'Parent Id',
            'Title',
            'Description',
            'Active',
            'Type',
            'Img Urls',
        ];
    }

    /**
     * @param Category $category
     * @return array
     */
    private function tableBody(Category $category): array
    {
        return [
            'id'            => $category->id,
            'uuid'          => $category->uuid,
            'keywords'      => $category->keywords,
            'parent_id'     => $category->parent_id,
            'title'         => data_get($category->translation, 'title', ''),
            'description'   => data_get($category->translation, 'description', ''),
            'active'        => $category->active ? 'active' : 'inactive',
            'type'          => data_get(Category::TYPES_VALUES, $category->type, 'main'),
            'img_urls'      => $this->imageUrl($category->galleries),
        ];
    }
}
