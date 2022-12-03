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


### Filter Class

If you want to select one or more value as a filter, like you only need to see rows from today, you can use:

    public function filters()
	{
		return [
            Filter::name('date', 'Select a Date'),
        ];
    }

this will let you filter from your table using `date` column, also you can skip the label name, by defualt it will be `Date`.

also you can use custom data and filters:

example 1 (if you want to filter by name first letter):

    Filter::name('name')->options(function () {
        $options = User::query()
            ->select([
                DB::raw('LEFT(name,1) as letter'),
                DB::raw('COUNT(*) as total')
            ])->groupBy('letter')->get();

        return $options->map(fn ($d) => [
            'value' => $d['letter'],
            'name' => "Starts with '{$d['letter']}'",
            'total' => $d['total'],
        ])->toArray();
    })
    ->filter(function (Builder $query, array $values) {
        $query->whereIn(DB::raw('LEFT(name,1)'), $values);
    })

for `options` you need to return array of options, and option has (name, value, total).

`filter` if this filter is selected you can add conditions to the provided query, and you will get also array of the selected values.

example 2(filter by success: success, fail)

    Filter::name('success')->options(function () {
        return [
            [
                'name' => 'Success',
                'value' => '>=',
                'total' => Exam::where('points', ">=", 50)->count()
            ],
            [
                'name' => 'Fail',
                'value' => '<',
                'total' => Exam::where('points', "<", 50)->count()
            ],
        ];
    })
    ->filter(function (Builder $query, array $values) {
        if(count($values) ==2) return;
        $val = current($values);
        $query->whereIn('points', $val, 50);
    })