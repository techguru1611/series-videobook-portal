@extends('layouts.admin-master')

@section('title')
    {{trans('title.EDIT_SETTING')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h3>
                </h3>
                    <ul class="nav nav-tabs" id="myTab">
                    <li class="active"><a data-toggle="tab" href="#android" class="typeToggle">Android</a></li>
                    <li><a data-toggle="tab" href="#ios" class="typeToggle">iOS</a></li>
                    </ul>

                    <div class="tab-content">
                            <div id="android" class="tab-pane fade in active ">
                                <form class="form-groups" role="form" id="appversion" method="post" action="{{ url('admin/appversion/set') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="">
                                    <input type="hidden" name="type" value="android">
                                    <div class="form-group row">
                                        <div class="col-md-12 text-right">
                                               <button type="button"  class="addlinkandroid" id="addlinkandroid">+<i class="la la-plus"></i></button>
                                        </div>
                                    </div>
                                    @php $androidCount = 0; @endphp
                                    @forelse($androidData as $_androidData)
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                          <label class="col-md-12 control-label" for="app_version">{{trans('admin-labels.APP_VERSION')}}</label>
                                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" placeholder="{{trans('place-holder.APP_VERSION')}}" name="app_version[]" value="{{$_androidData->app_version}}">
                                        </div>
                                         <div class="col-md-5">
                                            <label class="col-md-12 control-label" for="force_update">{{trans('admin-labels.FORCE_UPDATE')}}</label>
                                            <input type="hidden" name="force_update[{{$androidCount}}]" value="0">
                                            <input type="checkbox" name="force_update[{{$androidCount}}]" class="toggleCheckbox" value="1" @if($_androidData->force_update == 1) checked = "checked"  @endif >
                                        </div>
                                        <div class="col-md-1 text-left">
                                           <button><a href="{{ url('admin/appversion/delete') }}/{{$_androidData->id}}" class="removelink" >-<i class="la la-plus"></i></a></button>
                                        </div>
                                    </div>
                                    <?php
                                        if ($_androidData != $androidData->last()) {
                                            $androidCount++;
                                        }
                                    ?>
                                    @empty
                                     <div class="form-group row">
                                        <div class="col-md-6">
                                            <label class="col-md-12 control-label" for="app_version">{{trans('admin-labels.APP_VERSION')}}</label>
                                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" placeholder="{{trans('place-holder.APP_VERSION')}}" name="app_version[]" value="">
                                        </div>
                                         <div class="col-md-5">
                                            <label class="col-md-12 control-label" for="force_update">{{trans('admin-labels.FORCE_UPDATE')}}</label>
                                            <input type="hidden" name="force_update[0]" value="0">
                                            <input type="checkbox" name="force_update[0]" class="toggleCheckbox" checked value ="1">
                                        </div>
                                    </div>

                                    @endforelse
                                    <div  id="android_version"></div>

                                    <button type="submit" class="btn btn-success mr-2">{{ trans('admin-labels.SUBMIT') }}</button>
                                    <a href="{{ url('admin/appversion') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                            </form>
                            </div>

                            <div id="ios" class="tab-pane fade">
                                 <form class="form-groups" role="form" id="appversion" method="post" action="{{ url('admin/appversion/set') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="">
                                    <input type="hidden" name="type" value="ios">
                                    <div class="form-group row">
                                        <div class="col-md-12 text-right">
                                               <button type="button"  class="addlinkios" id="addlinkios">+<i class="la la-plus"></i></button>
                                        </div>
                                    </div>
                                    @php $iosCount = 0; @endphp
                                    @forelse($iosData as $_iosData)
                                    
                                    <div class="form-group row">       
                                        <div class="col-md-6">
                                            <label class="col-md-12 control-label" for="app_version">{{trans('admin-labels.APP_VERSION')}}</label>
                                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" placeholder="{{trans('place-holder.APP_VERSION')}}" name="app_version[]" value="{{$_iosData->app_version}}">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="col-md-12 control-label" for="force_update">{{trans('admin-labels.FORCE_UPDATE')}}</label>
                                            <input type="hidden" name="force_update[{{$iosCount}}]" value="0">
                                            <input type="checkbox" name="force_update[{{$iosCount}}]" @if($_iosData->force_update == 1) checked = "checked"  @endif class="toggleCheckbox" value="1">
                                        </div>
                                         <div class="col-md-1 text-left">
                                           <button><a href="{{ url('admin/appversion/delete') }}/{{$_iosData->id}}" class="removelink">-<i class="la la-plus"></i></a></button>
                                        </div>
                                    </div>
                                    <?php
                                        if ($_iosData != $iosData->last()) {
                                            $iosCount++;
                                        }
                                    ?>
                                    @empty
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label class="col-md-12 control-label" for="app_version">{{trans('admin-labels.APP_VERSION')}}</label>
                                            <input type="text" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" placeholder="{{trans('place-holder.APP_VERSION')}}" name="app_version[]" value="">
                                        </div>
                                         <div class="col-md-5">
                                            <label class="col-md-12 control-label" for="force_update">{{trans('admin-labels.FORCE_UPDATE')}}</label>
                                            <input type="hidden" name="force_update[0]" value="0">
                                            <input type="checkbox" name="force_update[0]" class="toggleCheckbox" checked value="1">
                                        </div>
                                    </div>

                                    @endforelse
                                    <div  id="ios_version"></div>

                                     <button type="submit" class="btn btn-success mr-2">{{ trans('admin-labels.SUBMIT') }}</button>

                                    <a href="{{ url('admin/appversion') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>

                                 </form>
                            </div>
                    </div>
            </div>
        </div>
 </div>

@endsection

@section('script')
<script>
    var androidCounter = parseInt('{{ $androidCount }}');
    var iosCounter  = parseInt("{{ $iosCount }}");
    $(document).ready(function() {
        setUpdateToggle();
        
        $("#addlinkandroid").click(function(){
            androidCounter = androidCounter + 1;
            $("#android_version").append(getFormat(androidCounter));
            setUpdateToggle();
        });

        $("#addlinkios").click(function(){
            iosCounter = iosCounter + 1;
            $("#ios_version").append(getFormat(iosCounter));
            setUpdateToggle();
        });


        $("#appversion").validate({
                rules: {
                    app_version: {
                        required: true
                    },
                    force_update:{
                       required: true 
                    }  
                }
            });
    });

   $(document).on('click', '.typeToggle', function() {
        setUpdateToggle();
   });

    function setUpdateToggle() {
        $(".toggleCheckbox").bootstrapToggle();
    }

    $(document).on('click', '.removelink', function() {
        $(this).parent().parent().remove();
    });

    function getFormat(counter) {
        return `<div class="form-group row">
                        <div class="col-md-6">
                        <input type="text" class="form-control" id="title" placeholder="{{trans('place-holder.APP_VERSION')}}" name="app_version[]" value="">
                        </div>

                        <div class="col-md-5">                 
                            <input type="hidden" name="force_update[`+counter+`]" value="0">          
                            <input type="checkbox" name="force_update[`+counter+`]"checked class="toggleCheckbox" data-toggle="toggle" value="1">
                        </div>

                        <div class="col-md-1 text-left">
                           <button type="button" class="removelink" >-<i class="la la-plus"></i></button>
                        </div>
                    </div>`;
    }

</script>
@endsection