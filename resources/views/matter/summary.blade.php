<div id="tocopy">
@foreach ($description as $line) 
{{ $line }} <BR />
@endforeach
</div>
<div class="float-end">
  <button id="sumButton" type="button" class="btn btn-primary" onclick="document.getElementById('ajaxModal').close()">Copy</button>
</div>
