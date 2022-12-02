<?php

namespace Mzprog\Datatables;

use Illuminate\Support\Str;


class Column {
    public string $name, $label, $field;

    public bool $is_raw = false;
    public bool $is_searchable = false, $is_orderable = false;
    public $search_cb = null, $order_cb = null;

    private $editColumn = null;

    static public function name($name, $label = null) : Column
    {
        $column = new self;
        $column->name = $name;
        $column->field = $name;
        $column->label = $label ?? Str::headline($name);

        return $column;
    }

    public function field($field)
    {
        $this->field = $field;

        return $this;
    }

    public function searchable(callable $cb=null)
    {
        $this->is_searchable = true;
        $this->search_cb = $cb;
        return $this;
    }

    public function orderable(callable $cb=null)
    {
        $this->is_orderable = true;
        $this->order_cb = $cb;
        return $this;
    }
    
    public function raw()
    {
        $this->is_raw = true;
        return $this;
    }

    public function edit(callable $cb)
    {
        $this->editColumn = $cb;
        return $this;
    }

    public function transfom($data)
    {
        if($this->editColumn){
            $cb = $this->editColumn;
            return $cb($data);
        }

        return $data->{$this->name};
    }

    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'searchable' => $this->is_searchable,
            'orderable' => $this->is_orderable,
            'raw' => $this->is_raw,
        ];
    }

    public function toArray()
    {
        return $this->__serialize();
    }
}
