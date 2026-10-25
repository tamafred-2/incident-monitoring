@extends('errors::minimal')

@section('title', __('Upload Too Large'))
@section('code', '413')
@section('message')
    {{ __('The selected photos are too large to upload at once. Choose smaller or fewer photos, then try again.') }}
    <a href="javascript:history.back()" style="margin-left: 0.75rem; text-decoration: underline;">{{ __('Go back') }}</a>
@endsection
