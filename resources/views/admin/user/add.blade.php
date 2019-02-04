@extends('layouts.admin-master')

@section('title')
    {{trans('title.ADD_USER')}}
@endsection

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                @if($user->id > 0)
                    {{trans('title.EDIT_USER')}}
                @else
                    {{trans('title.ADD_USER')}}
                @endif
                
                <form class="form-groups" role="form" id="user" method="post" action="{{ url('admin/users/set') }}" enctype="multipart/form-data">
                    @csrf
                    <?php $id = ($user->id > 0 && isset($user)) ? $user['id'] : '0'; ?>
                    <input type="hidden" name="id" value="{{\App\Services\UrlService::base64UrlEncode($id)}}">

                    <!-- User Full Name -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="full_name">{{trans('admin-labels.USER_FULL_NAME')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('full_name') ? 'is-invalid' : '' }}" id="full_name" placeholder="{{trans('place-holder.USER_FULL_NAME')}}" name="full_name" value="{{ old('full_name') ? old('full_name') : $user['full_name'] }}">
                            @if($errors->has('full_name'))
                                <div class="invalid-feedback">
                                    {{$errors->first('full_name')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Username -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="username">{{trans('admin-labels.USER_USER_NAME')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" id="username" placeholder="{{trans('place-holder.USER_USER_NAME')}}" name="username" value="{{ old('username') ? old('username') : $user['username'] }}">
                            @if($errors->has('username'))
                                <div class="invalid-feedback">
                                    {{$errors->first('username')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Email -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="email">{{trans('admin-labels.USER_EMAIL')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" id="email" placeholder="{{trans('place-holder.USER_EMAIL')}}" name="email" value="{{ old('email') ? old('email') : $user['email'] }}">
                            @if($errors->has('email'))
                                <div class="invalid-feedback">
                                    {{$errors->first('email')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Country Code -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="country_code">{{trans('admin-labels.USER_COUNTRY_CODE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('country_code') ? 'is-invalid' : '' }}" id="country_code" placeholder="{{trans('place-holder.USER_COUNTRY_CODE')}}" name="country_code" value="{{ old('country_code') ? old('country_code') : $user['country_code'] }}">
                            @if($errors->has('country_code'))
                                <div class="invalid-feedback">
                                    {{$errors->first('country_code')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Phone -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="phone_no">{{trans('admin-labels.USER_PHONE_NO')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control {{ $errors->has('phone_no') ? 'is-invalid' : '' }}" id="phone_no" placeholder="{{trans('place-holder.USER_PHONE_NO')}}" name="phone_no" value="{{ old('phone_no') ? old('phone_no') : $user['phone_no'] }}">
                            @if($errors->has('phone_no'))
                                <div class="invalid-feedback">
                                    {{$errors->first('phone_no')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Gender -->
                    <?php $gender = (old('gender') ? old('gender') : (($user->id) ? $user->gender : 'male')); ?>
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="gender">{{trans('admin-labels.USER_GENDER')}}</label>
                        <div class="col-md-6 form-group row">
                            <div class="col-md-4">
                                <div class="form-radio">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" name="gender" value='male' @if( ( old('gender') ? old('gender') : $gender ) == 'male') checked @endif > {{ trans('admin-labels.MALE') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-radio">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" name="gender" value='female' @if( ( old('gender') ? old('gender') : $gender ) == 'female') checked @endif > {{ trans('admin-labels.FEMALE') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-radio">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" name="gender" value='other' @if( ( old('gender') ? old('gender') : $gender ) == 'non-binary') checked @endif > {{ trans('admin-labels.OTHER') }}
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            @if($errors->has('gender'))
                                <div class="invalid-feedback">
                                    {{$errors->first('gender')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Birth Date -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="birth_date">{{trans('admin-labels.USER_BIRTH_DATE')}}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control datetimepicker {{ $errors->has('birth_date') ? 'is-invalid' : '' }}" id="birth_date" placeholder="{{trans('place-holder.USER_BIRTH_DATE')}}" name="birth_date" value="{{ old('birth_date') ? old('birth_date') : $user['birth_date'] }}">
                            @if($errors->has('birth_date'))
                                <div class="invalid-feedback">
                                    {{$errors->first('birth_date')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Photo -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="photo">{{trans('admin-labels.USER_PHOTO')}}</label>
                        <div class="col-md-6">
                            <input type="file" class="form-control {{ $errors->has('photo') ? 'is-invalid' : '' }}" id="photo" name="photo">
                            @if($errors->has('photo'))
                                <div class="invalid-feedback">
                                    {{$errors->first('photo')}}
                                </div>
                            @endif
                            <br>
                            @if(isset($user->photo) && !empty($user->photo))
                                <img src="{{ Storage::url(Config::get('constant.USER_THUMB_PHOTO_UPLOAD_PATH'). $user->photo)}}" height="80px">
                            @endif
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-md-2 control-label" for="status" class="col-md-2 control-label"> {{ trans('admin-labels.USER_STATUS') }} </label>
                        <div class="col-md-6">
                            <select class="form-control" id="status" name="status">
                                @foreach ($status as $_status)
                                    <option value="{{$_status['value']}}" @if($_status['value'] == ( old('status') ? old('status') : $user->status )) selected="selected" @endif >{{$_status['name']}}</option>
                                @endforeach
                            </select>
                            @if($errors->has('status'))
                                <div class="invalid-feedback">
                                    {{$errors->first('status')}}
                                </div>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mr-2">{{ trans('admin-labels.SUBMIT') }}</button>
                    <a href="{{ url('admin/users') }}" class="btn btn-light">{{ trans('admin-labels.CANCEL') }}</a>
                </form>
            </div>
        </div>
    </div>

@endsection


@section('style')
    <link rel="stylesheet" href="{{ asset('admin/css/bootstrap-datetimepicker.min.css') }}">
@endsection

@section('script')
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.js"></script>
<script type="text/javascript" src="{{ asset('js/admin/bootstrap-datetimepicker.min.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {

            $('#birth_date,#marriage_anniversary_date').datetimepicker({
                'format': 'YYYY-MM-DD'
            });

            $('#birth_date').data("DateTimePicker").maxDate(new Date());

            $('.datetimepicker').keydown(function(e) {
                e.preventDefault();
                return false;
            });


            var maxField = 5; //Input fields increment limitation
            var addButton = $('.add-btn'); //Add button selector
            var wrapper = $('.option-wrapper'); //Input field wrapper
            var fieldHTML = '<div class="row" style="margin-bottom: 10px">\n' +
            '                                <div class="col-md-3">\n' +
            '                                    <input class="form-control" type="text" name="option[]" placeholder="Ex - B">\n' +
            '                                </div>\n' +
            '                                <div class="col-md-8">\n' +
            '                                    <input class="form-control" type="text" name="value[]" placeholder="Ex - Manmohan Singh">\n' +
            '                                </div>\n' +
            '                                <div class="col-md-1 text-right">\n' +
            '                                    <a class="remove-btn btn btn-danger" href="javascript:void(0);" title="Add Option">\n' +
            '                                        <i class="fa fa-minus-circle"></i>\n' +
            '                                    </a>\n' +
            '                                </div>\n' +
            '                            </div>'; //New input field html
            var x = 1; //Initial field counter is 1

            //Once add button is clicked
            $(addButton).click(function(){
                //Check maximum number of input fields
                if(x < maxField){
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); //Add field html
                }
            });

            //Once remove button is clicked
            $(wrapper).on('click', '.remove-btn', function(e){
                e.preventDefault();
                $(this).parent().parent().remove(); //Remove field html
                x--; //Decrement field counter
            });

            var ID = '<?php echo $id; ?>';
            var userPhotoRequired = (ID == '0' ? true : false);

             $("#user").validate({
                ignore: ":hidden:not(select)",
                rules: {
                    full_name: {
                        required: true
                    },
                    username :{
                       required: true 
                    },
                    email :{
                       required: true ,
                       email:true
                    },
                    country_code :{
                       required: true 
                    },
                    phone_no :{
                       required: true,
                       number: true
                    },
                    gender :{
                       required: true 
                    },
                    birth_date:{
                        required: true
                    },
                    photo :{ 
                        required: userPhotoRequired,
                        extension: "png|jpeg|jpg|bmp",
                        filesize: 2,
                    },
                    status: {
                        required: true
                    },  
                }
            });
        });
    </script>
@endsection
