@include('errors.page', app(\App\Support\HttpErrorPage::class)->forRequest(500, request()))
