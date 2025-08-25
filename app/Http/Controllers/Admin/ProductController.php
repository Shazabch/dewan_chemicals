<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Products';
    }

    protected function getProducts()
    {
        return Product::searchable(['name', 'sku', 'alert_quantity'])->with('category:id,name', 'brand:id,name', 'unit:id,name', 'productStock:id,product_id,quantity,net_weight', 'saleDetails')->orderByDesc('id');
    }

    public function index()
    {
        $pageTitle = $this->pageTitle;
        $products  = $this->getProducts()->paginate(getPaginate());
        return view('admin.product.index', compact('pageTitle', 'products'));
    }

    public function productPDF()
    {
        $pageTitle = $this->pageTitle;
        $products  = $this->getProducts()->get();
        return downloadPDF('pdf.product.list', compact('pageTitle', 'products'));
    }
    public function productCSV()
    {
        $pageTitle = $this->pageTitle;
        $filename  = $this->downloadCsv($pageTitle, $this->getProducts()->get());
        return response()->download(...$filename);
    }

    protected function downloadCsv($pageTitle, $data)
    {
        $filename = "assets/files/csv/example.csv";
        $myFile   = fopen($filename, 'w');
        $column   = "Name,SKU,Category,Brand,Stock,Total Sale,Alert Qty,Unit\n";
        foreach ($data as $product) {
            $category = @$product->category->name;
            $brand    = @$product->brand->name;
            $stock    = @$product->totalInStock();
            $sale     = @$product->totalSale();
            $alert    = @$product->alert_quantity;
            $unit     = @$product->unit->name;

            $column .= "$product->name,$product->sku,$category,$brand,$stock,$sale, $alert,$unit\n";
        }
        fwrite($myFile, $column);
        $headers = [
            'Content-Type' => 'application/csv',
        ];
        $name  = $pageTitle . time() . '.csv';
        $array = [$filename, $name, $headers];
        return $array;
    }

    public function create()
    {
        $pageTitle  = 'Add Product';
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        return view('admin.product.form', compact('pageTitle', 'categories', 'brands', 'units', 'warehouses'));
    }
    public function openStock()
    {
        $pageTitle  = 'Product Open Stock';
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        return view('admin.product.open-stock', compact('pageTitle', 'products', 'warehouses'));
    }


    public function store(Request $request, $id = 0)
    {
        $this->validation($request, $id);
        if ($id) {
            $product      = Product::findOrFail($id);
            $notification = 'Product updated successfully';
        } else {
            $product      = new Product();
            $notification = 'Product added successfully';
        }
        if ($request->hasFile('image')) {
            try {
                $old            = $product->image;
                $product->image = fileUploader($request->image, getFilePath('product'), getFileSize('product'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your product image'];
                return back()->withNotify($notify);
            }
        }
        $product->name           = $request->name;
        $product->sku            = $request->sku;
        $product->category_id    = $request->category_id;
        $product->brand_id       = $request->brand_id;
        $product->unit_id        = $request->unit_id;
        $product->alert_quantity = $request->alert_quantity;
        $product->net_weight           = $request->net_weight;
        $product->note           = $request->note;
        $product->save();
        if ($request->warehouse_id && $request->stock_quantity) {
            $this->storeStock($request->warehouse_id, $product->id, $request->stock_quantity);
        }
        Action::newEntry($product, $id ? 'UPDATED' : 'CREATED');

        $notify[] = ['success',  $notification];
        return back()->withNotify($notify);
    }
    public function openStockStore(Request $request)
    {
        $this->validationStock($request);
        if ($request->product_id) {
            $product      = Product::findOrFail($request->product_id);
        }
        $notification = 'Stock updated successfully';

        if ($request->warehouse_id && $request->stock_quantity) {
            $this->storeStock($request->warehouse_id, $request->product_id, $request->stock_quantity, $request->net_weight);
        }

        $notify[] = ['success',  $notification];
        return back()->withNotify($notify);
    }
    protected function storeStock($warehouse_id, $productid, $quantity, $net_weight = null)
    {

        $previousStock = ProductStock::where('warehouse_id', $warehouse_id)->where('product_id', $productid)->first();
        if ($previousStock) {
            $previousStock->quantity += $quantity;
            $previousStock->net_weight += $net_weight ?? 0;
            $previousStock->save();
        } else {
            $stock               = new ProductStock();
            $stock->warehouse_id = $warehouse_id;
            $stock->product_id   = $productid;
            $stock->quantity     = $quantity;
            $stock->net_weight     = $net_weight ?? 0;
            $stock->save();
        }
    }

    public function edit($id)
    {
        $pageTitle  = 'Edit Product';
        $product    = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();
        return view('admin.product.form', compact('product', 'pageTitle', 'categories', 'brands', 'units'));
    }




    protected function validation($request, $id = 0)
    {
        $request->validate(
            [
                'name'           => 'required|string|unique:products,name,' . $id,
                'category_id'    => 'required|exists:categories,id',
                'sku'            => 'required|string|max:40|unique:products,sku,' . $id,
                'brand_id'       => 'nullable|exists:brands,id',
                'unit_id'        => 'nullable|exists:units,id',
                'alert_quantity' => 'nullable|numeric',
                'note'           => 'nullable|string|max:500',
                'image'          => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
            ],
            [
                'category_id.required' => 'The category field is required',
                'brand_id.required'    => 'The brand field is required',
                'unit_id.required'     => 'The unit field is required'
            ]
        );
    }
    protected function validationStock($request)
    {
        $request->validate(
            [
                'product_id'           => 'required',
                'warehouse_id'    => 'required',
                'stock_quantity'    => 'required',

            ],
            [
                'product_id.required' => 'The Product field is required',
                'warehouse_id.required'    => 'The Werehouse field is required',
                'stock_quantity.required'     => 'The Quantity field is required'
            ]
        );
    }
    public function alert()
    {
        $pageTitle = 'All Alerting Products';
        $products  = Product::searchable(['products.name']);
        $products->select('products.id', 'products.sku', 'products.name', 'units.name as unit_name', 'products.alert_quantity', 'product_stocks.quantity', 'warehouses.name as warehouse_name')
            ->join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->join('warehouses', 'warehouses.id', '=', 'product_stocks.warehouse_id')
            ->whereRaw('products.alert_quantity >= product_stocks.quantity');

        $products = $products->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.product.alert', compact('pageTitle', 'products'));
    }

    public function allProducts()
    {
        $products = Product::select('id', 'name', 'sku')->searchable(['name', 'sku'])->paginate(request()->rows ?? 5);
        return response()->json([
            'success'  => true,
            'products' => $products,
            'more'     => $products->hasMorePages()
        ]);
    }


    public function import(Request $request)
    {
        importFileValidation($request);
        $file    = $request->file('file');
        $csvData = file_get_contents($file->getRealPath());
        $rows    = array_map('str_getcsv', explode("\n", $csvData));
        array_shift($rows);
        array_pop($rows);

        $header = ['name', 'category_id', 'brand_id', 'unit_id', 'sku', 'alert_quantity', 'note'];

        $productData = [];
        foreach ($rows as $row) {
            if (count($header) === count($row)) {
                $data = array_combine($header, $row);
                if (in_array(null, $data) || $data === null) {
                    continue;
                }
                $category      = strtolower($data['category_id']);
                $brand         = strtolower($data['brand_id']);
                $unit          = strtolower($data['unit_id']);
                $checkCategory = Category::where('name', $category)->first();
                if (!$checkCategory) {
                    $notify[] = ['error', 'Mismatch in category: ' . $category];
                    return back()->withNotify($notify);
                }
                $checkBrand = Brand::where('name', $brand)->first();
                if (!$checkBrand) {
                    $notify[] = ['error', 'Mismatch in brand: ' . $brand];
                    return back()->withNotify($notify);
                }
                $checkUnit = Unit::where('name', $unit)->first();
                if (!$checkUnit) {
                    $notify[] = ['error', 'Mismatch in unit: ' . $unit];
                    return back()->withNotify($notify);
                }

                $data['name']              = $row[0];
                $data['category_id']       = $checkCategory->id;
                $data['brand_id']          = $checkBrand->id;
                $data['unit_id']           = $checkUnit->id;
                $data['sku']               = $row[4];
                $data['alert_quantity']    = $row[5];
                $data['note']              = $row[6];
                $productData[$data['sku']] = $data;
            }
        }
        $existingSKUs = Product::whereIn('sku', array_keys($productData))->pluck('sku')->toArray();
        $productData  = array_filter($productData, function ($item) use ($existingSKUs) {
            return !in_array($item['sku'], $existingSKUs);
        });
        if (count($productData) > 0) {
            Product::insert($productData);
            $notify[] = ['success', 'Product CSV imported successfully'];
        } else {
            $notify[] = ['error', 'No new products to import.'];
        }
        return back()->withNotify($notify);
    }
    public function remove($id)
    {
        $product = Product::findOrFail($id);

        if ($product->created_at == $product->updated_at) {
            $product->delete();
            $notify[] = ['success', 'Product has been deleted successfully.'];
        } else {

            $notify[] = ['error', 'This product cannot be deleted because it has already been updated or is in use.'];
        }

        return back()->withNotify($notify);
    }
}
