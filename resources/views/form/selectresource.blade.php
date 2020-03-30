<div class="{{$viewClass['form-group']}} {!! !$errors->has($column) ?: 'has-error' !!}">
    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">{!! $label !!}</label>
    <div class="{{$viewClass['field']}} select-resource">
        @include('admin::form.error')

        <app></app>

        <div class="input-group">
            <div {!! $attributes !!}></div>
            @if(! $disabled)
                <input name="{{$name}}" type="hidden" />
            @endif
            <div class="input-group-append">
                <div class="btn btn-{{$style}} " id="{{ $btnId }}">
                    &nbsp;<i class="feather icon-arrow-up"></i>&nbsp;
                </div>
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>