<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\PdfController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\CustomerViewController;
use App\Http\Controllers\SupplierViewController;
use App\Livewire\Admin\CustomerTransactions\CustomerTransaction;
use App\Livewire\Admin\DayBook\DayBookDetailComponent;
use App\Livewire\Admin\SupplierTransactions\SupplierTransaction;
use App\Models\Bank;
use App\Models\Customer;
use App\Models\CustomerTransaction as ModelsCustomerTransaction;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;



Route::namespace('Auth')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::get('/', 'showLoginForm')->name('login');
            Route::post('/', 'login')->name('login');
            Route::get('logout', 'logout')->middleware('admin')->withoutMiddleware('admin.guest')->name('logout');
        });

        // Admin Password Reset
        Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
            Route::get('reset', 'showLinkRequestForm')->name('reset');
            Route::post('reset', 'sendResetCodeEmail');
            Route::get('code-verify', 'codeVerify')->name('code.verify');
            Route::post('verify-code', 'verifyCode')->name('verify.code');
        });

        Route::controller('ResetPasswordController')->group(function () {
            Route::get('password/reset/{token}', 'showResetForm')->name('password.reset.form');
            Route::post('password/reset/change', 'reset')->name('password.change');
        });
    });
});

Route::middleware(['admin', 'admin.permission'])->group(function () {
    Route::controller('AdminController')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('chart/purchase-sale', 'purchaseAndSaleReport')->name('chart.purchase.sale');
        Route::get('chart/sales/sales-return', 'saleAndSaleReturnReport')->name('chart.sales.return');
        Route::get('chart/purchases/purchases-return', 'purchaseAndPurchaseReturnReport')->name('chart.purchases.return');
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile', 'profileUpdate')->name('profile.update');
        Route::get('password', 'password')->name('password');
        Route::post('password', 'passwordUpdate')->name('password.update');
        Route::get('banned', 'banned')->name('banned');

        //Notification
        Route::get('notifications', 'notifications')->name('notifications');
        Route::get('notification/read/{id}', 'notificationRead')->name('notification.read');
        Route::get('notifications/read-all', 'readAllNotification')->name('notifications.read.all');
        Route::post('notifications/delete-all', 'deleteAllNotification')->name('notifications.delete.all');
        Route::post('notifications/delete-single/{id}', 'deleteSingleNotification')->name('notifications.delete.single');

        //Report Bugs
        Route::get('request-report', 'requestReport')->name('request.report');
        Route::post('request-report', 'reportSubmit')->name('request.report.store');

        Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
    });

    Route::controller('StaffController')->prefix('staff')->name('staff.')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('save/{id?}', 'save')->name('save');
        Route::post('switch-status/{id}', 'status')->name('status');
        Route::get('login/{id}', 'login')->name('login');
    });

    Route::controller('RolesController')->prefix('roles')->name('roles.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('add', 'add')->name('add');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('save/{id?}', 'save')->name('save');
        Route::delete('delete/{id}', 'destroy')->name('destroy');
    });

    // permission
    Route::controller('PermissionController')->prefix('permissions')->name('permissions.')->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('update-permissions', 'updatePermissions')->name('update');
    });

    //Category Manage
    Route::controller('CategoryController')->name('product.category.')->prefix('category')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('delete/{id}', 'remove')->name('delete');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });

    // Brand Manage
    Route::controller('BrandController')->name('product.brand.')->prefix('brand')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('delete/{id}', 'remove')->name('delete');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });

    // Unit Manage
    Route::controller('UnitController')->name('product.unit.')->prefix('unit')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('delete/{id}', 'remove')->name('delete');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });

    // Product Manage
    Route::controller('ProductController')->name('product.')->prefix('product')->group(function () {
        Route::get('all/{scope?}', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('open-stock', 'openStock')->name('open-stock');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('store-stock', 'openStockStore')->name('store-stock');
        Route::get('alert', 'alert')->name('alert');
        Route::get('pdf', 'productPDF')->name('pdf');
        Route::get('csv', 'productCSV')->name('csv');
        Route::post('import', 'import')->name('import');
    });

    // Warehouse Manage
    Route::controller('WarehouseController')->name('warehouse.')->prefix('warehouse')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
        Route::post('status/{id}', 'status')->name('status');
    });
    Route::get('/admin/warehouse/warehouse-detail/{id}', function ($id) {
        $warehouse = Warehouse::findOrFail($id);
        $pageTitle = 'Warehouse Detail Page - ' . $warehouse->name;

        return view('admin.warehouse.warehouse-detail', compact('pageTitle', 'warehouse'));
    })->name('warehouse.detail');

    //Bank Manage
    Route::controller('BankController')->name('bank.')->prefix('bank')->group(function () {
        Route::get('manage', 'index')->name('index');
    });
    Route::get('/bank-detail/{id}', function ($id) {
        $bank = Bank::findOrFail($id);
        $pageTitle = 'Bank Detail Page -' . $bank->name;
        return view('bank.detail', compact('pageTitle', 'bank'));
    })->name('bank.detail');

    Route::get('/manage/sale', function () {
        $pageTitle = 'Manage Sale';
        return view('admin.sale.manage', compact('pageTitle'));
    })->name('manage_sale');
    Route::get('/services/stock-in', function () {
        $pageTitle = 'Manage Stock In';
        return view('admin.services.stock-in', compact('pageTitle'));
    })->name('manage_stock_in');
    Route::get('/production/flows', function () {
        $pageTitle = 'Production Flows';
        return view('admin.production.flow', compact('pageTitle'));
    })->name('production_flow');
    Route::get('/services/stock-out', function () {
        $pageTitle = 'Manage Stock Out';
        return view('admin.services.stock-out', compact('pageTitle'));
    })->name('manage_stock_out');
    Route::get('/services/stock-transfer', function () {
        $pageTitle = 'Manage Stock Transfer';
        return view('admin.services.stock-transfer', compact('pageTitle'));
    })->name('manage_stock_transfer');
    Route::get('/services/stock-details', function () {
        $pageTitle = 'Manage Stock Details';
        return view('admin.services.stock-details', compact('pageTitle'));
    })->name('manage_stock_details');
    Route::get('/services/stock-client-details', function () {
        $pageTitle = 'Stock Client Report';
        return view('admin.services.stock-client-details', compact('pageTitle'));
    })->name('stock_client_details');

    Route::controller('PdfController')->name('pdf.')->prefix('pdf')->group(function () {

        Route::get('pdf-stock-detail', 'stockDetailPDF')->name('stock-detail');
    });


    // Manage Purchase
    Route::controller('PurchaseController')->name('purchase.')->prefix('purchase')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'purchasePDF')->name('pdf');
        Route::get('csv', 'purchaseCSV')->name('csv');
        Route::get('add-new', 'addNew')->name('new');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store', 'store')->name('store');
        Route::get('pdf/{id}', 'downloadDetails')->name('invoice.pdf');
        Route::post('update/{id}', 'update')->name('update');
        Route::get('product-search', 'productSearch')->name('product.search');
        Route::get('invoice-check', 'invoiceCheck')->name('invoice.check');
    });

    Route::get('/manage/purchase', function () {
        $pageTitle = 'Manage Purchase';
        return view('admin.purchase.manage', compact('pageTitle'));
    })->name('manage_purchase');
    //Manage Purchase Return
    Route::controller('PurchaseReturnController')->name('purchase.return.')->prefix('purchase-return')->group(function () {
        Route::get('new/{id}', 'newReturn')->name('items');

        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'purchaseReturnPDF')->name('pdf');
        Route::get('csv', 'purchaseReturnCSV')->name('csv');
        Route::post('store/{id}', 'store')->name('store');

        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('update/{id}', 'update')->name('update');

        Route::get('pdf/{id}', 'downloadDetails')->name('invoice.pdf');
        Route::get('search-product', 'searchProduct')->name('search.product');
        Route::get('check-invoice', 'checkInvoice')->name('check.invoice');
    });

    //Manage Sales
    Route::controller('SaleController')->name('sale.')->prefix('sale')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'salePDF')->name('pdf');
        Route::get('csv', 'saleCSV')->name('csv');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');

        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('update/{id}', 'update')->name('update');
        Route::get('pdf/{id}', 'downloadInvoice')->name('invoice.pdf');
        Route::get('search-product', 'searchProduct')->name('search.product');
        Route::get('search-customer', 'searchCustomer')->name('search.customer');

        Route::get('last-invoice', 'lastInvoice')->name('last.invoice');
    });

    //Manage Sale Return
    Route::controller('SaleReturnController')->name('sale.return.')->prefix('sale-return')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'saleReturnPDF')->name('pdf');
        Route::get('csv', 'saleReturnCSV')->name('csv');
        Route::get('new/{id}', 'newReturn')->name('items');
        Route::post('store/{id}', 'store')->name('store');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('update/{id}', 'update')->name('update');
        Route::get('pdf/{id}', 'downloadInvoice')->name('invoice.pdf');
        Route::get('search-product', 'searchProduct')->name('search.product');
        Route::get('search-customer', 'searchCustomer')->name('search.customer');
    });


    //Adjustment
    Route::controller('AdjustmentController')->name('adjustment.')->prefix('adjustment')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'adjustmentPDF')->name('pdf');
        Route::get('csv', 'adjustmentCSV')->name('csv');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('details/{id}', 'detailsPDF')->name('details.pdf');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('update/{id}', 'update')->name('update');
        Route::get('search-product', 'searchProduct')->name('search.product');
    });



    // Supplier
    Route::controller('SupplierController')->name('supplier.')->prefix('supplier')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::get('pdf', 'supplierPDF')->name('pdf');
        Route::get('csv', 'supplierCSV')->name('csv');
        Route::post('import', 'import')->name('import');
    });
    Route::get('supplier/view/{id}', function ($id) {
        $supplier = Supplier::findOrFail($id);
        $pageTitle = 'Supplier Details - ' . $supplier->name;

        return view('admin.supplier.view', [
            'supplier' => $supplier,
            'pageTitle' => $pageTitle,
        ]);
    })->name('supplier.view');
    Route::delete('admin/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');


    // Customer
    Route::controller('CustomerController')->name('customer.')->prefix('customer')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::get('pdf', 'customerPDF')->name('pdf');
        Route::get('csv', 'customerCSV')->name('csv');
        Route::post('import', 'import')->name('import');

        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single');
        Route::post('send-notification/{id}', 'sendNotificationSingle')->name('notification.single');
        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send');
        Route::get('email/detail/{id}', 'emailDetails')->name('email.details');

        Route::get('list', 'list')->name('list');
        Route::get('count-by-segment/{methodName}', 'countBySegment')->name('segment.count');

        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log');
        Route::get('notification/history', 'notificationHistory')->name('notification.history');
    });

    Route::get('customer/view/{id}', function ($id) {
        $customer = Customer::findOrFail($id);
        $pageTitle = 'Customer Details - ' . $customer->name; // or any title you want

        return view('admin.customer.view', [
            'customer' => $customer,
            'pageTitle' => $pageTitle,
        ]);
    })->name('customer.view');

    Route::delete('admin/customers/{id}', [CustomerController::class, 'destroy'])->name('customer.destroy');
    Route::get('/customers-pdf', [PdfController::class, 'customersPdf'])->name('customers.pdf');
    Route::post('customer/advance', 'CustomerController@storeAdvance')->name('customer.advance.store');
    //Payment - Supplier
    Route::controller('SupplierPaymentController')->name('supplier.payment.')->prefix('supplier/payment')->group(function () {
        Route::get('index/{id}', 'index')->name('index');
        Route::post('all-payment/{id}', 'clearPayment')->name('clear');
        Route::post('store/{id?}', 'purchasePayment')->name('store');
        Route::post('receive-payment/{id}', 'purchaseReturnPayment')->name('receive.store');
        //download
        Route::get('pdf/{id}', 'customerPayPDF')->name('pdf');
    });
    Route::post('suppliers/advance', 'SupplierController@storeAdvance')->name('supplier.advance.store');

    //Payment - Customer
    Route::controller('CustomerPaymentController')->name('customer.payment.')->prefix('customer/payment')->group(function () {
        Route::post('all-payment/{id}', 'clearPayment')->name('clear');
        //sale
        Route::get('index/{id}', 'index')->name('index');
        Route::post('store/{id?}', 'salePayment')->name('store');
        //sale return
        Route::post('payable/{id}', 'storeCustomerPayablePayment')->name('payable.store');
        //download
        Route::get('pdf/{id}', 'customerPayPDF')->name('pdf');
    });

    //Manage warehouse Transfer
    Route::controller('TransferController')->name('transfer.')->prefix('transfer')->group(function () {
        Route::get('all', 'index')->name('index');
        Route::get('pdf', 'transferPDF')->name('pdf');
        Route::get('csv', 'transferCSV')->name('csv');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store', 'store')->name('store');
        Route::get('pdf/{id}', 'detailsPDF')->name('details.pdf');
        Route::post('update/{id}', 'update')->name('update');
        Route::get('search-product', 'searchProduct')->name('search.product');
    });


    //Expense
    Route::controller('ExpenseTypeController')->name('expense.type.')->prefix('expense-type')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('delete/{id}', 'remove')->name('delete');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });
    Route::get('/manage/expense', function () {
        $pageTitle = 'Manage Expense';
        return view('admin.expense.manage', compact('pageTitle'));
    })->name('manage_expense');

    Route::controller('ExpenseController')->name('expense.')->prefix('expense')->group(function () {
        Route::get('pdf', 'expensePDF')->name('pdf');
        Route::get('csv', 'expenseCSV')->name('csv');
        Route::get('/{id?}', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('import', 'import')->name('import');
    });
    Route::get('/manage/expense-type', function () {
        $pageTitle = 'Manage Expense Type';
        return view('admin.expense.e-type', compact('pageTitle'));
    })->name('manage_expense_type');


    //Payment Report
    Route::controller('PaymentReportController')->name('report.payment.')->prefix('reports/payment')->group(function () {
        Route::get('supplier', 'supplierPaymentLogs')->name('supplier');
        Route::get('supplier/pdf', 'supplierPaymentPDF')->name('supplier.pdf');
        Route::get('supplier/csv', 'supplierPaymentCSV')->name('supplier.csv');
        Route::get('customer', 'customerPaymentLogs')->name('customer');
        Route::get('customer/pdf', 'customerPaymentPDF')->name('customer.pdf');
        Route::get('customer/csv', 'customerPaymentCSV')->name('customer.csv');
    });

    //Stock Report
    Route::controller('StockReportController')->name('report.stock.')->prefix('reports/stock')->group(function () {
        Route::get('index', 'index')->name('index');
        Route::get('pdf', 'stockPDF')->name('pdf');
        Route::get('csv', 'stockCSV')->name('csv');
    });


    Route::get('all-products', 'ProductController@allProducts')->name('product.list');

    Route::controller('DataEntryReportController')->prefix('reports/data-entry')->name('report.data.entry.')->group(function () {
        Route::get('product', 'product')->name('product');
        Route::get('customer', 'customer')->name('customer');
        Route::get('supplier', 'supplier')->name('supplier');
        Route::get('purchase', 'purchase')->name('purchase');
        Route::get('purchase-return', 'purchaseReturn')->name('purchase.return');
        Route::get('sale', 'sale')->name('sale');
        Route::get('sale-return', 'saleReturn')->name('sale.return');
        Route::get('adjustment', 'adjustment')->name('adjustment');
        Route::get('transfer', 'transfer')->name('transfer');
        Route::get('expense', 'expense')->name('expense');
        Route::get('supplier-payment', 'supplierPayment')->name('supplier.payment');
        Route::get('customer-payment', 'customerPayment')->name('customer.payment');
    });



    // Plugin
    Route::controller('ExtensionController')->prefix('extensions')->name('extensions.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('status/{id}', 'status')->name('status');
    });



    Route::controller('GeneralSettingController')->group(function () {

        Route::get('system-setting', 'systemSetting')->name('setting.system');

        // General Setting
        Route::get('general-setting', 'general')->name('setting.general');
        Route::post('general-setting', 'generalUpdate')->name('setting.general.update');

        //configuration
        Route::get('setting/system-configuration', 'systemConfiguration')->name('setting.system.configuration');
        Route::post('setting/system-configuration', 'systemConfigurationSubmit')->name('setting.system.configuration.update');

        // Logo-Icon
        Route::get('setting/logo-icon', 'logoIcon')->name('setting.logo.icon');
        Route::post('setting/logo-icon', 'logoIconUpdate')->name('setting.logo.icon');
    });



    //Notification Setting
    Route::name('setting.notification.')->controller('NotificationController')->prefix('notification')->group(function () {
        //Template Setting
        Route::get('global/email', 'globalEmail')->name('global.email');
        Route::post('global/email/update', 'globalEmailUpdate')->name('global.email.update');

        Route::get('global/sms', 'globalSms')->name('global.sms');
        Route::post('global/sms/update', 'globalSmsUpdate')->name('global.sms.update');

        Route::get('templates', 'templates')->name('templates');
        Route::get('template/edit/{type}/{id}', 'templateEdit')->name('template.edit');
        Route::post('template/update/{type}/{id}', 'templateUpdate')->name('template.update');

        //Email Setting
        Route::get('email/setting', 'emailSetting')->name('email');
        Route::post('email/setting', 'emailSettingUpdate')->name('email.update');
        Route::post('email/test', 'emailTest')->name('email.test');

        //SMS Setting
        Route::get('sms/setting', 'smsSetting')->name('sms');
        Route::post('sms/setting', 'smsSettingUpdate')->name('sms.update');
        Route::post('sms/test', 'smsTest')->name('sms.test');
    });


    //System Information
    Route::controller('SystemController')->name('system.')->prefix('system')->group(function () {
        Route::get('info', 'systemInfo')->name('info');
        Route::get('server-info', 'systemServerInfo')->name('server.info');
        Route::get('optimize', 'optimize')->name('optimize');
        Route::get('optimize-clear', 'optimizeClear')->name('optimize.clear');
        Route::get('system-update', 'systemUpdate')->name('update');
        Route::post('system-update', 'systemUpdateProcess')->name('update.process');
        Route::get('system-update/log', 'systemUpdateLog')->name('update.log');
    });
    //Day Book Banks
    Route::get('/day-book', function () {
        $pageTitle = 'Day Book';
        return view('admin.daybook.index', compact('pageTitle'));
    })->name('daybook.index');
    Route::get('/day-book-details/{date}', function () {
        $pageTitle = 'Day Book Details';
        return view('admin.daybook.detail', compact('pageTitle'));
    })->name('daybook.detail');
    Route::get('/admin/daybook/pdf/{date}', [DayBookDetailComponent::class, 'generatePdf'])->name('daybook.pdf');
    Route::get('/supplier/invoice/download/{supplierId}', [SupplierTransaction::class, 'generateInvoice'])->name('supplier.invoice');
});
