<?php

namespace Mzprog\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\WithPagination;


abstract class Datatable extends Component
{
    use WithPagination;
    protected $paginationTheme;
    public $theme;
    public int $pageLength;

    // order
    public string $orderBy = '';
    public string $orderDir = 'ASC';

    // filter
    public string $search = "";
    public array $filtersData = [];

    abstract public function query() : Builder;

    public function mount()
    {
        $this->pageLength = $this->pageLength ?? Config::get('datatables.page-length');
        // we need to fix the reapeated code, in mount and boot, it's fine for now
        $this->theme = $this->theme ?? Config::get('datatables.theme');
    }
    public function boot()
    {
        // protected value will not be saved every request
        $this->paginationTheme = $this->theme ?? Config::get('datatables.theme'); 
    }

    /**
     * @return Column[]
     */
    abstract public function columns(): array;

    public function setOrder(string $column)
    {
        if($column === $this->orderBy){
            if($this->orderDir === "ASC"){
                $this->orderDir = "DESC";
            }else{
                $this->orderBy = "";
            }
        }else{
            $this->orderBy = $column;
            $this->orderDir = 'ASC';
        }
    }

    private function doOrder(Builder $query)
    {
        if($this->orderBy != ''){
            /** @var Column $column */
            $column = collect($this->columns())->where('name', $this->orderBy)->first();
            if($column->order_cb){
                $order = $column->order_cb;
                $order($query, $this->orderDir);
            }else{
                $query->orderBy($column->field, $this->orderDir);
            }
        }
    }
    
    public function doSearch(Builder $query)
    {
        if($this->search != ''){
            $query->where(function($query){ // new query param for "or" inside the bracket
                collect($this->columns())->where('is_searchable', true)
                ->each(function(Column $column) use($query){
                    if($column->search_cb){
                        $search = $column->search_cb;
                        $query->orWhere(fn($q) =>  $search($q, $this->search));
                    }else{
                        $query->where($column->field,'like', "%{$this->search}%", 'or');
                    }
                });
            });
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function getData()
    {
        $query = $this->query();

        $this->doOrder($query);
        $this->doSearch($query);
        $this->doFilters($query);

        /** @var LengthAwarePaginator $data */
        $data = $query->paginate($this->pageLength);

        // tranform data
        $columns = $this->columns();
        $data->getCollection()->transform(function ($row) use($columns){
            foreach ($columns as $column) {
                $row->{$column->name} = $column->transfom($row);
            }
            return $row;
        });

        return $data;
    }

    public function getFilters()
    {
        if( ! method_exists($this, 'filters')){
            return [];
        }
        
        /** @var Collection $filtersSetup */
        $filtersSetup = collect($this->filters());

        $filters = $filtersSetup->map(function(Filter $f){
            $f->setQuery($this->query());

            return [
                'name' => $f->name,
                'label' => $f->label,
                'options' => $f->getOptions()
            ];
        })
        ->filter(fn($f) => count($f['options'])  > 1 )
        ->values();
        
        return $filters;
    }

    public function doFilters(Builder $query)
    {
        /** @var Collection $filtersSetup */
        $filtersSetup = collect($this->filters());

        $filtersSetup->each(function(Filter $f) use ($query) {
            if(array_key_exists($f->name, $this->filtersData)){
                $f->doFilter($query, $this->filtersData[$f->name]);
            }
        });
    }

    public function render()
    {
        $columns = collect($this->columns())->map(fn($c) => $c->toArray())->toArray();
        $data = $this->getData();
        $hasSearch =  collect($columns)->filter(fn($c) => $c['searchable'])->count()>0;
        $filters = $this->getFilters();
      
        return View::make('datatables::datatable-bs',[
            'columns' => $columns,
            'data' => $data,
            'hasSearch' => $hasSearch,
            'filters' => $filters,
        ]);
    }
}
