<?php

namespace Mzprog\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Filter {

    public string $name, $label;

    public $options_cb = null, $filter_cb = null; 

    protected Builder $query;

    static public function name(string $name, $label = null) : Filter
    {
        $column = new self;
        $column->name = $name;
        $column->label = $label ?? Str::headline($name);

        return $column;
    }

    public function options(callable $cb) : Filter
    {
        $this->options_cb = $cb;

        return $this;
    }
    public function filter(callable $cb) : Filter
    {
        $this->filter_cb = $cb;

        return $this;
    }

    public function setQuery(Builder $query)
    {
        $this->query = $query;
    }

    public function getOptions()
    {
        if($this->options_cb){
            $cb = $this->options_cb;
            return $cb();
        }

        $data = $this->query
        ->groupBy($this->name)
        ->get([
            $this->name,
            DB::raw("COUNT({$this->name}) as total"),
        ]);

        return $data->map(fn($d) => [
                'value' => $d[$this->name],
                'name' => $d[$this->name],
                'total' => $d['total'],
            ])->toArray();
    }

    public function doFilter(Builder $query, array $filters)
    {
        if($this->filter_cb){
            $cb = $this->filter_cb;
            $cb($query, $filters);

            return;
        }

        $query->whereIn($this->name, $filters);
    }
}