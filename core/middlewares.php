<?
require __DIR__ . '/../App/middlewares/AuthMiddleware.php';


$admin = function () {
    AuthMiddleware::handle(2);
};

$auth = function () {
    AuthMiddleware::handle();
};