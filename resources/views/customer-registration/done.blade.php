<div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6 text-center">
                <h1 class="text-lg font-semibold text-gray-800">Thank you{{ $registered ? ', '.$registered['name'] : '' }}!</h1>
                <p class="mt-2 text-sm text-gray-500">Your details have been received.</p>
                <div class="mt-5 rounded-lg bg-amber-50 px-4 py-4 text-left">
                    <p class="text-sm font-semibold text-amber-800">Your registration is waiting for approval</p>
                    <p class="mt-1 text-sm text-amber-700">
                        Our team will review it and prepare your program and schedule. Once it is approved you will receive an email
                        with your customer ID and a link to log in to your customer portal.
                    </p>
                </div>
                @if ($registered)
                    <p class="mt-4 text-xs text-gray-500">A confirmation was sent to <span class="font-medium text-gray-700">{{ $registered['email'] }}</span>.</p>
                @endif
            </div>
        </div>
    </body>
</html>
