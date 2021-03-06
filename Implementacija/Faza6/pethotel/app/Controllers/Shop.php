<?php
//Autor: Dušan Stanivuković 2017/0605

namespace App\Controllers;

use App\Models\OrderArticleModel;
use App\Models\ShopModel;
use App\Models\UserOrderModel;

/**
 * Shop - klasa za prikaz, unos i naručivanje proizvoda iz prodavnice
 *
 * @package App\Controllers
 *
 * @version 1.0
 */
class Shop extends BaseController
{
    /**
     * @var int $itemsPerPage Maksimalan broj proizvoda koji se prikazuju na jednoj stranici
     */
    private $itemsPerPage = 12;

    /**
     * Funkcija za prikaz proizvoda
     *
     * @param int $page Redni broj stranice sa proizvodima
     *
     * @return string
     */
    public function showArticles($page = 1)
    {
        session()->set("dogs", "true");
        session()->set("cats", "true");
        session()->set("birds", "true");
        session()->set("fishes", "true");
        session()->set("littleAnimals", "true");
        session()->remove("searchName");

        $shopModel = new ShopModel();
        $articles = $shopModel->findAll($this->itemsPerPage, ($page - 1) * $this->itemsPerPage);
        $data["title"] = "Pregled prodavnice";
        $data["name"] = "shop";
        $pagination["page"] = $page;
        $pagination["numOfPages"] = ceil($shopModel->countAll() / $this->itemsPerPage);
        echo view("templates/header", ["data" => $data]);
        echo view("shop/review", ["articles" => $articles, "pagination" => $pagination]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za prikaz odabranih kategorija proizvoda
     *
     * @param int $page Redni broj stranice sa proizvodima
     *
     * @return string
     */
    public function showArticlesByCategory($page = 1)
    {
        $data["title"] = "Pregled prodavnice";
        $data["name"] = "shop";

        $dogs = session()->get("dogs");
        $cats = session()->get("cats");
        $birds = session()->get("birds");
        $fishes = session()->get("fishes");
        $littleAnimals = session()->get("littleAnimals");

        if ($dogs == "true" && $cats == "true" && $birds == "true" && $fishes == "true" && $littleAnimals == "true"
            && !session()->has("articleName")) {
            return $this->showArticles($page);
        }

        $articles = $this->findArticles();

        $pagination["page"] = $page;
        $pagination["numOfPages"] = ceil(sizeof($articles) / $this->itemsPerPage);

        $offset = ($page - 1) * $this->itemsPerPage;
        $articles = array_slice($articles, $offset, $this->itemsPerPage);

        echo view("templates/header", ["data" => $data]);
        echo view("shop/review", ["articles" => $articles, "pagination" => $pagination, "categories" => true]);
        echo view("templates/footer");
    }

    /**
     * Pomoćna funkcija za dohvatanje proizvoda iz baze
     *
     * @return array
     */
    private function findArticles()
    {
        $shopModel = new ShopModel();

        $dogs = session()->get("dogs");
        $cats = session()->get("cats");
        $birds = session()->get("birds");
        $fishes = session()->get("fishes");
        $littleAnimals = session()->get("littleAnimals");

        $articles = [];

        if (session()->has("searchName")) {
            $articleName = session()->get("searchName");
            if ($dogs == "true")
                $articles = array_merge($articles, $shopModel->like("description", "psi#")
                    ->like("name", $articleName)
                    ->findAll());
            if ($cats == "true")
                $articles = array_merge($articles, $shopModel->like("description", "macke#")
                    ->like("name", $articleName)
                    ->findAll());
            if ($birds == "true")
                $articles = array_merge($articles, $shopModel->like("description", "ptice#")
                    ->like("name", $articleName)
                    ->findAll());
            if ($fishes == "true")
                $articles = array_merge($articles, $shopModel->like("description", "ribe#")
                    ->like("name", $articleName)
                    ->findAll());
            if ($littleAnimals == "true")
                $articles = array_merge($articles, $shopModel->like("description", "maleZivotinje#")
                    ->like("name", $articleName)
                    ->findAll());
        } else {
            if ($dogs == "true")
                $articles = array_merge($articles, $shopModel->like("description", "psi#")->findAll());
            if ($cats == "true")
                $articles = array_merge($articles, $shopModel->like("description", "macke#")->findAll());
            if ($birds == "true")
                $articles = array_merge($articles, $shopModel->like("description", "ptice#")->findAll());
            if ($fishes == "true")
                $articles = array_merge($articles, $shopModel->like("description", "ribe#")->findAll());
            if ($littleAnimals == "true")
                $articles = array_merge($articles, $shopModel->like("description", "maleZivotinje#")->findAll());
        }

        for ($i = 0; $i < sizeof($articles); $i++) {
            $pos = strpos($articles[$i]["description"], "#");
            $category = substr($articles[$i]["description"], 0, $pos);

            if (($dogs != "true" && $category == "psi")
                || ($cats != "true" && $category == "macke")
                || ($birds != "true" && $category == "ptice")
                || ($fishes != "true" && $category == "ribe")
                || ($littleAnimals != "true" && $category == "maleZivotinje")) {
                unset($articles[$i]);
                $articles = array_values($articles);
            }
        }

        return $articles;
    }

    /**
     * Funkcija koja vraća određene kategorije proizvoda kao odgovor na ajax zahtev
     *
     * @param int $page Redni broj stanice sa prozvodima
     *
     * @return string
     */
    public function searchCategories($page = 1)
    {
        $dogs = $this->request->getVar("dogs");
        $cats = $this->request->getVar("cats");
        $birds = $this->request->getVar("birds");
        $fishes = $this->request->getVar("fishes");
        $littleAnimals = $this->request->getVar("littleAnimals");

        session()->set("dogs", $dogs);
        session()->set("cats", $cats);
        session()->set("birds", $birds);
        session()->set("fishes", $fishes);
        session()->set("littleAnimals", $littleAnimals);

        $articles = $this->findArticles();
        $baseUrl = base_url();

        $offset = ($page - 1) * $this->itemsPerPage;
        $articles = array_slice($articles, $offset, $this->itemsPerPage);

        if (empty($articles)) {
            echo "<div class='alert alert-info alert-dismissible text-center mx-auto my-4'>";
            echo "<strong>Nema proizvoda</strong>";
            echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>";
            echo "<span aria-hidden='true'>&times;</span>";
            echo "</button>";
            echo "</div>";
        }

        foreach ($articles as $article) {
            $value = "                <div class='col-md-3'>\n";
            $value .= "                    <form method='post' action='$baseUrl/Shop/article'>\n";
            $value .= "                        <div class='card text-center mb-4'>\n";
            $value .= "                            <input type='image' src=" . "$baseUrl/images/shop/" . $article["image"] . " class='card-img-top'>\n";
            $value .= "                            <div class='card-body'>\n";
            $value .= "                                 <input name='article' type='hidden' value='" . $article["articleId"] . "'>\n";
            $value .= "                                 <input class='card-title btn btn-link button-link' type='submit' value='" . $article["name"] . "'>\n";
            $value .= "                                 <p class='card-text'>\n";
            $value .= "                                    Cena: <span class='font-weight-bold'>" . $article["price"] . " RSD</span><br>\n";

            $value .= "                                    <input name='order-btn' type='submit' class='btn btn-primary mt-2' value='Naruči'>\n";
            $value .= "                                </p>\n";
            $value .= "                            </div>\n";
            $value .= "                        </div>\n";
            $value .= "                    </form>\n";
            $value .= "               </div>\n";
            echo $value;
        }

        echo "#delimiter#";

        echo "<input type='hidden' id='page' value='" . $page . "'>\n";
        $prevPage = $page - 1;
        $nextPage = $page + 1;
        $prevDisabled = "";
        if ($page == 1) $prevDisabled = "disabled";
        echo "<li class='page-item $prevDisabled'>"
            . "<a id='prev-page' class='page-link' href='" . site_url('Shop/showArticlesByCategory/' . $prevPage)
            . "' tabindex='-1' aria-disabled='true'>Prethodna</a>"
            . "</li>";

        $articles = $this->findArticles();
        $numOfPages = ceil(sizeof($articles) / $this->itemsPerPage);

        for ($i = $prevPage; $i < $prevPage + 3; $i++) {
            if ($i >= 1 && $i <= $numOfPages) {
                if ($i == $page) {
                    echo "<li class='page-item active'>"
                        . "<a class='page-link' href='"
                        . site_url('Shop/showArticlesByCategory/' . $i)
                        . "'>$i <span class=\"sr-only\">(current)</span></a>"
                        . "</li>";
                } else {
                    echo "<li class='page-item'>"
                        . "<a class='page-link' href='"
                        . site_url('Shop/showArticlesByCategory/' . $i) . "'>$i</a>"
                        . "</li>";
                }
            }
        }

        $nextDisabled = "";
        if ($page >= $numOfPages) $nextDisabled = "disabled";
        echo "<li class='page-item $nextDisabled'>"
            . "<a class='page-link' href='" . site_url('Shop/showArticlesByCategory/' . $nextPage)
            . "'>Sledeća</a>"
            . "</li>";
    }

    /**
     * Funkcija za prikaz odabranih proizvoda na osnovu imena
     *
     * @param int $page Redni broj stranice sa proizvodima
     *
     * @return string
     */
    public function showArticlesByName($page = 1)
    {
        $shopModel = new ShopModel();

        $data["title"] = "Pregled prodavnice";
        $data["name"] = "shop";

        $name = session()->get("searchName");

        $articles = $shopModel->like("name", $name)->findAll();

        $pagination["page"] = $page;
        $pagination["numOfPages"] = ceil(sizeof($articles) / $this->itemsPerPage);

        $offset = ($page - 1) * $this->itemsPerPage;
        $articles = array_slice($articles, $offset, $this->itemsPerPage);

        echo view("templates/header", ["data" => $data]);
        echo view("shop/review", ["articles" => $articles, "pagination" => $pagination, "names" => true]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za pretragu proizvoda po imenima koja vraća određene proizvode kao odgovor na ajax zahtev
     *
     * @param int $page Redni broj stranice sa proizvodima
     *
     * @return string
     */
    public function searchNames($page = 1)
    {
        $shopModel = new ShopModel();

        $name = $this->request->getVar("name");

        session()->set("searchName", $name);

        $offset = ($page - 1) * $this->itemsPerPage;
        $articles = $shopModel->like("name", $name)->findAll($this->itemsPerPage, $offset);

        $baseUrl = base_url();

        if (empty($articles)) {
            echo "<div class='alert alert-info alert-dismissible text-center mx-auto my-4'>";
            echo "<strong>Nema proizvoda</strong>";
            echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>";
            echo "<span aria-hidden='true'>&times;</span>";
            echo "</button>";
            echo "</div>";
        }

        foreach ($articles as $article) {
            $value = "                <div class='col-md-3'>\n";
            $value .= "                    <form method='post' action='$baseUrl/Shop/article'>\n";
            $value .= "                        <div class='card text-center mb-4'>\n";
            $value .= "                            <input type='image' src=" . "$baseUrl/images/shop/" . $article["image"] . " class='card-img-top'>\n";
            $value .= "                            <div class='card-body'>\n";
            $value .= "                                 <input name='article' type='hidden' value='" . $article["articleId"] . "'>\n";
            $value .= "                                 <input class='card-title btn btn-link button-link' type='submit' value='" . $article["name"] . "'>\n";
            $value .= "                                 <p class='card-text'>\n";
            $value .= "                                    Cena: <span class='font-weight-bold'>" . $article["price"] . " RSD</span><br>\n";

            $value .= "                                    <input name='order-btn' type='submit' class='btn btn-primary mt-2' value='Naruči'>\n";
            $value .= "                                </p>\n";
            $value .= "                            </div>\n";
            $value .= "                        </div>\n";
            $value .= "                    </form>\n";
            $value .= "               </div>\n";
            echo $value;
        }

        echo "#delimiter#";

        echo "<input type='hidden' id='page' value='" . $page . "'>\n";
        $prevPage = $page - 1;
        $nextPage = $page + 1;
        $prevDisabled = "";
        if ($page == 1 || sizeof($articles) == 0) $prevDisabled = "disabled";
        echo "<li class='page-item $prevDisabled'>"
            . "<a id='prev-page' class='page-link' href='" . site_url('Shop/showArticlesByName/' . $prevPage)
            . "' tabindex='-1' aria-disabled='true'>Prethodna</a>"
            . "</li>";

        $numOfPages = ceil(sizeof($shopModel->like("name", $name)->findAll()) / $this->itemsPerPage);

        if (sizeof($articles) > 0) {
            for ($i = $prevPage; $i < $prevPage + 3; $i++) {
                if ($i >= 1 && $i <= $numOfPages) {
                    if ($i == $page) {
                        echo "<li class='page-item active'>"
                            . "<a class='page-link' href='"
                            . site_url('Shop/showArticlesByName/' . $i)
                            . "'>$i <span class=\"sr-only\">(current)</span></a>"
                            . "</li>";
                    } else {
                        echo "<li class='page-item'>"
                            . "<a class='page-link' href='"
                            . site_url('Shop/showArticlesByName/' . $i) . "'>$i</a>"
                            . "</li>";
                    }
                }
            }
        }

        $nextDisabled = "";
        if ($page >= $numOfPages || sizeof($articles) == 0) $nextDisabled = "disabled";
        echo "<li class='page-item $nextDisabled'>"
            . "<a class='page-link' href='" . site_url('Shop/showArticlesByName/' . $nextPage)
            . "'>Sledeća</a>"
            . "</li>";
    }

    /**
     * Funkcija za prikaz detalja o pojedinačnim proizvodima
     *
     * @param int $articleId Identifikator proizvoda
     *
     * @return string
     */
    public function article($articleId = null)
    {
        if ($articleId == null)
            $articleId = $this->request->getVar("article");
        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);
        $data["title"] = "Proizvod " . $article["name"];
        $data["name"] = "shop";
        echo view("templates/header", ["data" => $data]);
        echo view("shop/article", ["article" => $article]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za naručivanje proizvoda
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @throws \ReflectionException
     */
    public function order()
    {
        $amount = $this->request->getVar("amount");
        $articleId = $this->request->getVar("articleId");
        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);
        $data["title"] = "Proizvod " . $article["name"];
        $data["name"] = "shop";

        if ($article["amount"] - intval($amount) >= 0) {
            $article["amount"] -= intval($amount);
            $shopModel->update($articleId, $article);
            $messages = "Proizvod je dodat u korpu";

            $username = session()->get("username");

            $userOrderModel = new UserOrderModel();
            $orderArticleModel = new OrderArticleModel();

            $order = $userOrderModel->where("username", $username)->where("status", "open")->findAll(1);
            if ($order != null)
                $order = $order[0];

            if ($order == null) {
                $messages .= " i kreirana je narudžbina";

                $userOrderModel->save([
                    "username" => $username,
                    "dateTime" => date("Y-m-d H:i:s"),
                    "status" => "open",
                    "orderPrice" => 0
                ]);

                $orderArticleModel->setPrimaryKey("articleId");
                $item = $orderArticleModel->where("orderId", $userOrderModel->getInsertID())->find($articleId);

                if ($item != null) {
                    $item["amount"] += $amount;
                    $orderArticleModel->where("orderId", $order["orderId"])->update($articleId, $item);
                } else {
                    $orderArticleModel->setPrimaryKey("");
                    $orderArticleModel->save([
                        "orderId" => $userOrderModel->getInsertID(),
                        "articleId" => $articleId,
                        "articlePrice" => $article["price"],
                        "amount" => $amount
                    ]);
                }

                $order = $userOrderModel->find($userOrderModel->getInsertID());
                $order["orderPrice"] += ($amount * $article["price"]);
                $userOrderModel->update($order["orderId"], $order);

                return redirect()->to(site_url("Shop/article/" . $articleId))->with("messages", $messages);
            } else {
                $orderArticleModel->setPrimaryKey("articleId");
                $item = $orderArticleModel->where("orderId", $order["orderId"])->find($articleId);

                if ($item != null) {
                    $item["amount"] += $amount;
                    $orderArticleModel->where("orderId", $order["orderId"])->update($articleId, $item);
                } else {
                    $orderArticleModel->setPrimaryKey("");
                    $orderArticleModel->save([
                        "orderId" => $order["orderId"],
                        "articleId" => $articleId,
                        "articlePrice" => $article["price"],
                        "amount" => $amount
                    ]);
                }

                $order["orderPrice"] += ($amount * $article["price"]);
                $userOrderModel->update($order["orderId"], $order);

                return redirect()->to(site_url("Shop/article/" . $articleId))->with("messages", $messages);
            }
        } else {
            if ($article["amount"] > 0)
                $messages = "Proizvod nije dostupan u odabranoj količini";
            else
                $messages = "Proizvod nije na stanju";
            return redirect()->to(site_url("Shop/article/" . $articleId))->with("messages", $messages);
        }
    }

    /**
     * Funkcija za prikaz forme za unos proizvoda
     *
     * @return string
     */
    public function insertForm()
    {
        $data["title"] = "Unos proizvoda";
        $data["name"] = "shop";

        echo view("templates/header", ["data" => $data]);
        echo view("shop/articleInput");
        echo view("templates/footer");
    }

    /**
     * Funkcija za unos proizvoda u bazu podataka
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @throws \ReflectionException
     */
    public function insertArticle()
    {
        if (!$this->validate([
            "name" => "required|min_length[3]|max_length[32]",
            "price" => "required",
            "amount" => "required",
            "image" => "uploaded[image]|max_size[image,5120]|ext_in[image,jpg,jpeg,png,jfif,gif]",
            "category" => "required",
            "description" => "max_length[240]"
        ], [
            "name" => [
                "required" => "Morate uneti naziv",
                "min_length" => "Naziv mora sadržati najmanje 3 karaktera",
                "max_length" => "Naziv može sadržati najviše 32 karaktera"
            ],
            "price" => ["required" => "Morate uneti cenu"],
            "amount" => ["required" => "Morate uneti količinu"],
            "image" => [
                "uploaded" => "Morate odabrati sliku",
                "max_size" => "Veličina slike je veća od dozvoljene",
                "ext_in" => "Pogrešna ekstenzija odabrane slike"
            ],
            "category" => ["required" => "Morate odabrati kategoriju"],
            "description" => ["max_length" => "Opis može sadržati najviše 240 karaktera"]
        ])) {
            $userType = session()->get("userType");
            if ($userType == "admin")
                return redirect()->to(site_url("Admin/insertArticle"))->with(
                    "messages", $this->validator->listErrors());
            else if ($userType == "moderator")
                return redirect()->to(site_url("Moderator/insertArticle"))->with(
                    "messages", $this->validator->listErrors());
        }

        $name = $this->request->getVar("name");
        $price = $this->request->getVar("price");
        $amount = $this->request->getVar("amount");
        $image = $this->request->getFile("image");
        $category = $this->request->getVar("category");
        $description = $this->request->getVar("description");

        $image->move(ROOTPATH . "/public/images/shop");

        $shopModel = new ShopModel();

        $descriptionCategory = $category . "#" . $description;

        $shopModel->save([
            "name" => $name,
            "price" => $price,
            "amount" => $amount,
            "image" => $image->getName(),
            "description" => $descriptionCategory
        ]);

        $userType = session()->get("userType");
        if ($userType == "admin")
            return redirect()->to(site_url("Admin/insertArticle"))->with(
                "messages", "Uspešno ste uneli proizvod");
        else if ($userType == "moderator")
            return redirect()->to(site_url("Moderator/insertArticle"))->with(
                "messages", "Uspešno ste uneli proizvod");
    }

    /**
     * Funkcija za brisanje proizvoda iz baze podataka
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function deleteArticle()
    {
        $articleId = $this->request->getVar("delete");
        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);
        unlink(realpath(ROOTPATH . "/public/images/shop/" . $article["image"]));
        $shopModel->delete($articleId);
        if (session()->get("userType") == "admin")
            return redirect()->to(site_url("Admin/manageArticles"));
        else
            return redirect()->to(site_url("Moderator/manageArticles"));
    }

    /**
     * Funkcija za prikaz forme za promenu podataka o proizvodu
     *
     * @param int $articleId Identifikator proizvoda u bazi podataka
     *
     * @return string
     */
    public function changeArticle($articleId = null)
    {
        if ($articleId == null)
            $articleId = $this->request->getVar("change");
        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);
        $data["title"] = "Proizvod " . $article["name"];
        $data["name"] = "shop";
        echo view("templates/header", ["data" => $data]);
        echo view("shop/changeArticle", ["article" => $article]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za izmenu podataka o proizvodu
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @throws \ReflectionException
     */
    public function saveChanges()
    {
        $userType = session()->get("userType");
        $articleId = $this->request->getVar("articleId");

        if (!$this->validate([
            "name" => "required|min_length[3]|max_length[32]",
            "price" => "required",
            "amount" => "required",
            "category" => "required",
            "description" => "max_length[240]"
        ], [
            "name" => [
                "required" => "Morate uneti naziv",
                "min_length" => "Naziv mora sadržati najmanje 3 karaktera",
                "max_length" => "Naziv može sadržati najviše 32 karaktera"
            ],
            "price" => ["required" => "Morate uneti cenu"],
            "amount" => ["required" => "Morate uneti količinu"],
            "category" => ["required" => "Morate odabrati kategoriju"],
            "description" => ["max_length" => "Opis može sadržati najviše 240 karaktera"]
        ])) {
            if ($userType == "admin" || $userType == "moderator")
                return redirect()->to(site_url("Shop/changeArticle/" . $articleId))->with(
                    "messages", $this->validator->listErrors());
        }

        $name = $this->request->getVar("name");
        $price = $this->request->getVar("price");
        $amount = $this->request->getVar("amount");
        $category = $this->request->getVar("category");
        $description = $this->request->getVar("description");

        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);

        $descriptionCategory = $category . "#" . $description;

        $article["name"] = $name;
        $article["price"] = $price;
        $article["amount"] = $amount;
        $article["description"] = $descriptionCategory;

        $shopModel->update($articleId, $article);

        if ($userType == "admin" || $userType == "moderator")
            return redirect()->to(site_url("Shop/changeArticle/" . $articleId))->with(
                "messages", "Uspešno ste izmenili podatke o proizvodu");
    }

    /**
     * Funkcija za prikaz korpe sa narudžbinama
     *
     * @return string
     */
    public function cart()
    {
        $data["title"] = "Korpa";
        $data["name"] = "cart";

        $username = session()->get("username");

        $userOrderModel = new UserOrderModel();

        $orders = $userOrderModel->where("username", $username)->findAll();

        echo view("templates/header", ["data" => $data]);
        echo view("shop/cart", ["orders" => $orders]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za prikaz detalja o narudžbini
     *
     * @return string
     */
    public function showOrder()
    {
        $data["title"] = "Prikaz narudžbine";
        $data["name"] = "cart";

        $orderId = $this->request->getVar("orderId");
        $userOrderModel = new UserOrderModel();
        $order = $userOrderModel->find($orderId);

        $orderArticleModel = new OrderArticleModel();
        $orderArticles = $orderArticleModel->where("orderId", $orderId)->findAll();

        $articles = [];
        $shopModel = new ShopModel();
        foreach ($orderArticles as $orderArticle) {
            $articles = array_merge($articles, $shopModel->where("articleId", $orderArticle["articleId"])->findAll());
        }

        echo view("templates/header", ["data" => $data]);
        echo view("shop/order", ["order" => $order, "orderArticles" => $orderArticles, "articles" => $articles]);
        echo view("templates/footer");
    }

    /**
     * Pomoćna funkcija za pronalaženje količine proizvoda
     *
     * @param array $orderArticles Niz stavki narudžbina
     * @param array $article Niz sa podacima o proizvodu
     *
     * @return string
     */
    private function findAmount($orderArticles, $article)
    {
        foreach ($orderArticles as $orderArticle) {
            if ($orderArticle["articleId"] == $article["articleId"])
                return $orderArticle["amount"] . "#" . $article["articleId"];
        }
        return "";
    }

    /**
     * Funkcija za uklanjanje proizvoda iz korpe
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function removeArticleFromCart()
    {
        $articleId = $this->request->getVar("articleId");
        $orderId = $this->request->getVar("orderId");

        $orderArticleModel = new OrderArticleModel();
        $orderArticle = $orderArticleModel->where("orderId", $orderId)->find($articleId);
        $amount = $orderArticle["amount"];

        $orderArticleModel->where("orderId", $orderId)->where("articleId", $articleId)->delete();

        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);
        $article["amount"] += $amount;
        $shopModel->update($articleId, $article);

        $userOrderModel = new UserOrderModel();
        $order = $userOrderModel->find($orderId);
        $order["orderPrice"] -= ($amount * $article["price"]);
        $userOrderModel->update($orderId, $order);

        echo "Ukupan iznos: " . $order["orderPrice"] . " RSD#delimiter#";

        $orderArticles = $orderArticleModel->where("orderId", $orderId)->findAll();

        $articles = [];
        $shopModel = new ShopModel();
        foreach ($orderArticles as $orderArticle) {
            $articles = array_merge($articles, $shopModel->where("articleId", $orderArticle["articleId"])->findAll());
        }

        $baseUrl = base_url();

        echo '<input type="hidden" id="base" value="' . $baseUrl . '">';

        foreach ($articles as $article) {
            $amountId = $this->findAmount($orderArticles, $article);
            $amount = explode("#", $amountId);

            $value = "                <div class='col-md-3'>\n";
            $value .= "                    <div class='card text-center mb-4'>\n";
            $value .= "                         <img src=" . "$baseUrl/images/shop/" . $article["image"] . " class='card-img-top'>\n";
            $value .= "                         <div class='card-body'>\n";
            $value .= "                             <input name='article' type='hidden' value='" . $article["articleId"] . "'>\n";
            $value .= "                             <h5 class='card-title'>" . $article["name"] . "</h5>\n";
            $value .= "                             <p class='card-text'>\n";
            $value .= "                                 Količina: <span id='articleAmount" . $article["articleId"] . "' class='font-weight-bold'>"
                . $amount[0] . "</span><br>\n";
            $value .= "                                 Cena: <span class='font-weight-bold'>" . $article["price"] . " RSD</span><br>\n";
            $value .= "                                 Ukupna cena: <span id='articlePrice" . $article["articleId"] . "' class='font-weight-bold'>"
                . intval($amount[0] * $article["price"]) . " RSD</span><br>\n";

            $value .= "                                 <button type='button' onclick='removeArticle(\"" . $article["articleId"]
                . "\")' class='btn bg-transparent'><i class='far fa-trash-alt'></i></button>\n";
            $value .= "                                 <button data-toggle='modal' data-target='#exampleModalCenter' onclick='showAmount(\""
                . $amountId . "\")' type='button' class='btn btn-primary mt-2'>Promeni količinu</button>\n";
            $value .= "                             </p>\n";
            $value .= "                         </div>\n";
            $value .= "                    </div>\n";
            $value .= "               </div>\n";
            echo $value;
        }
    }

    /**
     * Funkcija za promenu količine naručenog proizvoda
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function changeAmount()
    {
        $amount = $this->request->getVar("amount");
        $articleId = $this->request->getVar("articleId");
        $orderId = $this->request->getVar("orderId");

        $orderArticleModel = new OrderArticleModel();
        $orderArticle = $orderArticleModel->where("orderId", $orderId)->find($articleId);

        $shopModel = new ShopModel();
        $article = $shopModel->find($articleId);

        if ($amount == $orderArticle["amount"]) {
            return "error";
        }

        $userOrderModel = new UserOrderModel();
        $order = $userOrderModel->find($orderId);

        $oldAmount = $orderArticle["amount"];

        if ($amount < $orderArticle["amount"]) {
            $article["amount"] += ($orderArticle["amount"] - $amount);
            $orderArticle["amount"] = $amount;

            $shopModel->update($articleId, $article);
            $orderArticleModel->where("orderId", $orderId)->update($articleId, $orderArticle);

            $order["orderPrice"] -= (($oldAmount - $amount) * $article["price"]);
            $userOrderModel->update($orderId, $order);

            return $amount . "#" . ($amount * $article["price"]) . " RSD#Ukupan iznos: " . $order["orderPrice"] . " RSD";
        } else if ($amount - $orderArticle["amount"] <= $article["amount"]) {
            $article["amount"] -= ($amount - $orderArticle["amount"]);
            $orderArticle["amount"] = $amount;

            $shopModel->update($articleId, $article);
            $orderArticleModel->where("orderId", $orderId)->update($articleId, $orderArticle);

            $order["orderPrice"] += (($amount - $oldAmount) * $article["price"]);
            $userOrderModel->update($orderId, $order);

            return $amount . "#" . ($amount * $article["price"]) . " RSD#Ukupan iznos: " . $order["orderPrice"] . " RSD";
        }

        return "error";
    }

    /**
     * Funkcija za otkazivanje narudžbine
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @throws \ReflectionException
     */
    public function cancelOrder()
    {
        $orderId = $this->request->getVar("orderId");

        $orderArticleModel = new OrderArticleModel();
        $orderArticles = $orderArticleModel->where("orderId", $orderId)->findAll();

        $shopModel = new ShopModel();

        foreach ($orderArticles as $orderArticle) {
            $article = $shopModel->find($orderArticle["articleId"]);
            $article["amount"] += $orderArticle["amount"];
            $shopModel->update($article["articleId"], $article);
        }

        $userOrderModel = new UserOrderModel();
        $userOrderModel->delete($orderId);

        return redirect()->to(site_url("Shop/cart"));
    }

    /**
     * Funkcija za prikaz forme za potvrdu narudžbine
     *
     * @return string
     */
    public function finishOrderForm()
    {
        $data["title"] = "Potvrda narudžbine";
        $data["name"] = "cart";

        $orderId = $this->request->getVar("orderId");

        echo view("templates/header", ["data" => $data]);
        echo view("shop/finishOrder", ["orderId" => $orderId]);
        echo view("templates/footer");
    }

    /**
     * Funkcija za potvrđivanje narudžbine
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     *
     * @throws \ReflectionException
     */
    public function finishOrder()
    {
        $orderId = $this->request->getVar("orderId");
        session()->set("orderId", $orderId);

        if (!$this->validate([
            "street" => "required|min_length[5]|max_length[32]",
            "city" => "required|min_length[3]|max_length[32]",
            "postalCode" => "required|min_length[5]|max_length[5]"
        ], [
            "street" => [
                "required" => "Morate uneti ulicu",
                "min_length" => "Ulica mora sadržati najmanje 5 karaktera",
                "max_length" => "Ulica može sadržati najviše 32 karaktera",
            ],
            "city" => [
                "required" => "Morate uneti grad",
                "min_length" => "Grad mora sadržati najmanje 3 karaktera",
                "max_length" => "Grad može sadržati najviše 32 karaktera",
            ],
            "postalCode" => [
                "required" => "Morate uneti poštanski broj",
                "min_length" => "Poštanski broj mora sadržati tačno 5 karaktera",
                "max_length" => "Poštanski broj mora sadržati tačno 5 karaktera",
            ]
        ])) {
            return redirect()->to(site_url("Shop/finishOrderForm"))->with("messages", $this->validator->listErrors());
        }

        $street = $this->request->getVar("street");
        $city = $this->request->getVar("city");
        $state = $this->request->getVar("state");
        $postalCode = $this->request->getVar("postalCode");

        $userOrderModel = new UserOrderModel();
        $order = $userOrderModel->find($orderId);

        $order["status"] = "closed";
        $order["recipientAddress"] = $street;
        $order["recipientCity"] = $city;
        $order["recipientState"] = $state;
        $order["recipientPostalCode"] = $postalCode;

        $userOrderModel->update($orderId, $order);

        session()->remove("orderId");

        return redirect()->to(site_url("Shop/cart"));
    }

}

















