<span class="float-right">
    @if($filters->isSortedBy($field) !== false)
        @if($filters->isSortedBy($field) == 'asc')
            <a href="{{ $filters->makeSortUrl($field,'desc') }}">@svg('/img/icons/sort_up.svg','sort_up')</a>
        @else
            <a href="{{ $filters->makeSortUrl($field,'asc') }}">@svg('/img/icons/sort_down.svg','sort_down')</a>
        @endif
    @else
        <a href="{{ $filters->makeSortUrl($field,'asc') }}">@svg('/img/icons/sort.svg','sort')</a>
    @endif
 </span>