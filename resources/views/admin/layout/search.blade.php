<form>
    <input type="text" class="form-control" size="30" name="q" value="{{ Request::input('q') }}" placeholder="{{ $placeholder or 'Search' }}">
</form>
@if (Request::has('q'))
    <a href="{{ Request::url() }}" class="pull-right">Clear Search</a>
    <br><br>
@endif