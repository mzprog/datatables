# datatables
create a better tables with few lines of code.

## Installation
simply install the package by:

    composer require mzprog/datatables

make sure that livewire is installed, and styles and scripts added to the blade layout: `@livewireStyles` and `@livewireScripts`


## Basic Usage

- create a livewire component, without view file, and without `render` method.
- The component should extends `Mzprog\Datatables\Datatable`
- add `columns` method to your component 
    - `public function columns(): array;`
    - This Should return array of `Mzprog\Datatables\Column`
- add `query` method to have the main query
    - `public function query() : Builder`

### Column class
just add `Column::name('id')` to your columns array, and it will be added and viewed in your table, but it will be labeled as `Id`.

To change the label name just use the second parameter `Column::name('id', 'ID')`.

The name will be used as the array index, and database field  by defualt.

`field` method is used to allow you to different key for the column as in this example(`orderable`& `edit` will be explained later):

    Column::name('added')->field('created_at')
        ->orderable()
        ->edit(fn($row) => $row->created_at->diffForHumans()),

we have change the format for created_at but can't store it in same properties (because casted to date, and work as `getCreateAtAttribute`).

so when use `orderable` it will order using `created_at`.


`edit` method is used to change or add data.<br />
it accept callback with the current raw data as parameter, example:

    Column::name('full_name', 'Name)
        ->edit(fn($raw) => "{$raw->first_name} {$raw->last_name}")

`orderable`method will allow you to order the column by pressing on the column name.
it will order your data based on your field name, unless you add a callback as a parameter. 
the callback parameter are: the query `Builder`, and the order direction, example:

    return [
        Column::name('id', 'ID')->orderable(),
        Column::name('full_name', 'Name)
            ->orderable(fn($q, $dir) => $q->orderBy('first_name', $dir)->orderBy('last_name', $dir))
            ->edit(fn($raw) => "{$raw->first_name} {$raw->last_name}"),
    ];

`searchable` method is work as orderable, and the callback function will pass the search keyword instead of order direction, example:

    Column::name('full_name', 'Name)
        ->searchable(
            fn($q, $keyword) => $q
                ->where(DB::raw('CONCAT(first_name," ", last_name)'), 'like', "%${keyword}%")
        )
        ->orderable(fn($q, $dir) => $q->orderBy('first_name', $dir)->orderBy('last_name', $dir))
        ->edit(fn($raw) => "{$raw->first_name} {$raw->last_name}"),

`raw` method is used when you need to print HTML in your table. example:

    Column::name('actions')->raw()
        ->edit(fn($row) => '<a href="' . route('users.show',['user' => $row->id]) . '" >View</a>';