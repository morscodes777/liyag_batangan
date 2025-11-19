<?php
require_once __DIR__ . '/../models/vendorModel.php';

class AdminController {
    private $vendorModel;

    public function __construct() {
        $this->vendorModel = new VendorModel();
    }

    private function render($view, $data = []) {
        extract($data);
        require __DIR__ . "/../views/$view.php";
    }

    public function dashboard() {
        $pending_vendors = $this->vendorModel->getPendingVendors();

        // Mock for now (replace later with models if you like)
        $pending_products = [
            ['id' => 201, 'name' => 'Barako Coffee', 'vendor' => 'Vendor A'],
            ['id' => 202, 'name' => 'Kapeng Puti', 'vendor' => 'Vendor C'],
        ];
        $vendor_sales = [
            ['vendor' => 'Vendor A', 'sales' => 55000],
            ['vendor' => 'Vendor B', 'sales' => 32000],
            ['vendor' => 'Vendor C', 'sales' => 78000],
        ];
        $total_sales = array_sum(array_column($vendor_sales, 'sales'));

        $this->render('admin_dashboard', [
            'pending_vendors'  => $pending_vendors,
            'pending_products' => $pending_products,
            'vendor_sales'     => $vendor_sales,
            'total_sales'      => $total_sales
        ]);
    }
}
