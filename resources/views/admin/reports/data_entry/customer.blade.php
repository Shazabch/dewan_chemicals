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
                                    <th>@lang('Name')</th>
                                    <th>@lang('Mobile')</th>
                                    @include('admin.reports.data_entry.partials.common_headings')
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.customer.index') }}?search={{ $entry->actionable?->mobile }}">{{ $entry->actionable?->name }}</a>

                                        </td>

                                        <td>
                                            +{{ $entry->actionable?->mobile }}
                                        </td>
                                        @include('admin.reports.data_entry.partials.common_columns')
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>

                @if ($entries->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($entries) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection
