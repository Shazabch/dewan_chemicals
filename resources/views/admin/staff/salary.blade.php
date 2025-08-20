@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Staff Name : {{ $staff->name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff List</a></li>
                        <li class="breadcrumb-item active">Manage Salary</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @livewire('admin.staff.salary-component', ['user' => $staff])
</div>
@endsection