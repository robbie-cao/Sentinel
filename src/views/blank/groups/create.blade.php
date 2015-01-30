@extends(Config::get('Sentinel::views.layout'))

{{-- Web site Title --}}
@section('title')
@parent
Create Group
@stop

{{-- Content --}}
@section('content')

<form method="POST" action="{{ route('sentinel.groups.store') }}" accept-charset="UTF-8">
    
    <h2>Create New Group</h2>
    
    <p>
        <input class="form-control" placeholder="Name" name="name" type="text" value="{{ Input::old('name') }}">
        {{ ($errors->has('name') ? $errors->first('name') : '') }}
    </p>

    <?php $defaultPermissions = Config::get('Sentinel::auth.default_permissions', []); ?>

    <p>  
        Permissions
        <ul>
            @foreach ($defaultPermissions as $permission)
                <li>
                    <input name="permissions[{{ $permission }}]" value="1" type="checkbox"
                    @if (Input::old('permissions[' . $permission .']'))
                        checked
                    @endif        
                    > {{ ucwords($permission) }}
                </li>
            @endforeach
        </ul>
    </p>

    <input name="_token" value="{{ csrf_token() }}" type="hidden">
    <input value="Create New Group" type="submit">

</form>
    
@stop