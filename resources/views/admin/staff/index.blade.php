@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive--md table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('S.N.')</th>
                                <th>@lang('Username')</th>
                                <th>@lang('Name')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Role')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allStaff as $staff)
                            <tr>
                                <td>{{ $loop->index + $allStaff->firstItem() }}</td>
                                <td>{{ $staff->username }}</td>
                                <td>{{ __($staff->name) }}</td>
                                <td>{{ $staff->email }}</td>
                                <td>
                                    @if ($staff->role)
                                    {{ $staff->role->name }}
                                    @else
                                    @lang('Super Admin')
                                    @endif
                                </td>

                                <td>
                                    @php
                                    echo $staff->statusBadge;
                                    @endphp
                                </td>
                                <td>
                                    <div class="button--group">
                                        @if ($staff->id > 1)
                                        <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                            data-resource="{{ $staff }}" data-modal_title="@lang('Update Staff')">
                                            <i class="la la-pencil"></i>@lang('Edit')
                                        </button>
                                        @if ($staff->status)
                                        <button class="btn btn-sm confirmationBtn btn-outline--danger"
                                            data-action="{{ route('admin.staff.status', $staff->id) }}" data-question="@lang('Are you sure to ban this staff?')"
                                            type="button">
                                            <i class="las la-user-alt-slash"></i>@lang('Ban')
                                        </button>
                                        @else
                                        <button class="btn btn-sm confirmationBtn btn-outline--success"
                                            data-action="{{ route('admin.staff.status', $staff->id) }}" data-question="@lang('Are you sure to active this staff?')"
                                            type="button">
                                            <i class="las la-user-check"></i>@lang('Active')
                                        </button>
                                        @endif
                                        <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.staff.login', $staff->id) }}"
                                            target="_blank">
                                            <i class="las la-sign-in-alt"></i>@lang('Login')
                                        </a>
                                        <a href="{{ route('admin.staff.salary', $staff->id) }}"
                                            class="btn btn-sm btn-warning"
                                            title="Manage Salary">
                                            <i class="fas fa-money-bill-wave"></i> {{-- Example using Font Awesome --}}
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($allStaff->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($allStaff) }}
            </div>
            @endif
        </div>
    </div>
</div>
<x-confirmation-modal />

<!-- Create Update Modal -->
<div class="modal fade" id="cuModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>

            <form action="{{ route('admin.staff.save') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-group">
                        <label>@lang('Name')</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>@lang('Username')</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>

                    <div class="form-group">
                        <label>@lang('Email')</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>@lang('Role')</label>
                        <select name="role_id" class="form-control select2" required>
                            <option value="" disabled selected>@lang('Select One')</option>
                            @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>@lang('Password')</label>
                        <div class="input-group">
                            <input class="form-control" name="password" type="text" required>
                            <button class="input-group-text generatePassword" type="button">@lang('Generate')</button>
                        </div>
                    </div>
                </div>
                {{-- @permit('admin.staff.save') --}}
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
                {{-- @endpermit --}}
            </form>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<x-search-form placeholder="Username" />
{{-- @permit('admin.staff.save') --}}
<!-- Modal Trigger Button -->
<button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New Staff')">
    <i class="las la-plus"></i>@lang('Add New')
</button>
{{-- @endpermit --}}
@endpush

@push('script-lib')
<script src="{{ asset('assets/admin/js/cu-modal.js') }}"></script>
@endpush

@push('script')
<script>
    (function($) {
        "use strict";
        $('.generatePassword').on('click', function() {
            $(this).siblings('[name=password]').val(generatePassword());
        });

        $('.cuModalBtn').on('click', function() {
            let passwordField = $('#cuModal').find($('[name=password]'));
            let label = passwordField.parents('.form-group').find('label')
            if ($(this).data('resource')) {
                passwordField.removeAttr('required');
                label.removeClass('required')
            } else {
                passwordField.attr('required', 'required');
                label.addClass('required')
            }
        });

        function generatePassword(length = 12) {
            let charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+<>?/";
            let password = '';

            for (var i = 0, n = charset.length; i < length; ++i) {
                password += charset.charAt(Math.floor(Math.random() * n));
            }

            return password
        }
    })(jQuery);
</script>
@endpush