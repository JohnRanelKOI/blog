<?php
    require("./../includes/global_variables.php");
    include_once(SITE_ROOT . "/helpers/authentication.php");
    include_once(SITE_ROOT . "/includes/config.php");
    require_once(SITE_ROOT . "/models/blog.php");
    require_once(SITE_ROOT . "/models/image.php");

    $blog_posts_init = new Blog($db_conn);
    $post_image_init = new Image($db_conn);

    switch($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            if(array_key_exists("id", $_GET)) {
                echo fetchBlogById($blog_posts_init);
            }
            if(array_key_exists("skip", $_GET) && array_key_exists("limit", $_GET)) {
                echo paginateBlog($blog_posts_init);
            }
            if(array_key_exists("count", $_GET)) {
                echo getBlogTotalCount($blog_posts_init);
            }
            break;
        case "POST":
            unauthorizedAccessRedirect();
            if(array_key_exists("type", $_GET) && $_GET["type"] === "create") {
                echo insertBlogPost($blog_posts_init, $post_image_init);
            } else {
                echo updateBlogPost($blog_posts_init, $post_image_init);
            }
            break;
        case "DELETE":
            unauthorizedAccessRedirect();
            deleteBlogPost($blog_posts_init, $post_image_init);
            break;
    }

    function fetchBlogById($blog_posts_init) {
        $blog_post_id = $_GET["id"];
        $blog_post = $blog_posts_init->getPostById($blog_post_id);
        return json_encode($blog_post->fetch_assoc());
    }

    function paginateBlog($blog_posts_init) {
        $limit = $_GET["limit"];
        $skip = $_GET["skip"];
        $blog_post = $blog_posts_init->getLimitedPosts($limit, $skip);
        $rows = [];
        if ($blog_post->num_rows > 0) {
            while ($row = $blog_post->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return json_encode($rows);
    }

    function getBlogTotalCount($blog_posts_init) {
        $blog_post_count = $blog_posts_init->getCount();
        $blog_count = $blog_post_count->fetch_assoc();
        return $blog_count["count"];
    }

    function insertBlogPost($blog_posts_init, $post_image_init) {
        $title = htmlspecialchars($_POST["title"] ?? '');
        $slug = convertTitleToURL($_POST["title"] ?? '');
        $short_description = htmlspecialchars($_POST["short_description"] ?? '');
        $content = htmlspecialchars($_POST["content"] ?? '');
        $category = htmlspecialchars($_POST["category"] ?? '');
        $date = htmlspecialchars($_POST["date"] ?? '');
        $file = $_FILES["post_image"];
        $new_blog_post_id = $blog_posts_init->insertNewPost($_SESSION["user_id"], "blog", $title, $slug, $short_description, $content, $category, $date);
        $post_image_init->insertNewImage($new_blog_post_id, $file, SITE_ROOT, SITE_URL);
        return $new_blog_post_id;
    }

    function updateBlogPost($blog_posts_init, $post_image_init) {
        $id = htmlspecialchars($_POST["id"] ?? '');
        $title = htmlspecialchars($_POST["title"] ?? '');
        $slug = convertTitleToURL($_POST["title"] ?? '');
        $short_description = htmlspecialchars($_POST["short_description"] ?? '');
        $content = htmlspecialchars($_POST["content"] ?? '');
        $category = htmlspecialchars($_POST["category"] ?? '');
        $date = htmlspecialchars($_POST["date"] ?? '');
        if(isset($_FILES["post_image"])) {
            $post_image_init->updateImage($_FILES["post_image"], $_POST["image_url"], SITE_ROOT);
        }
        $blog_posts_init->updatePost($id, $title, convertTitleToURL($title), $short_description, $content, $category, $date);
    }

    function deleteBlogPost($blog_posts_init, $post_image_init) {
        $file_path = SITE_ROOT . "/uploads/" . basename($_GET["image_url"]);
        $post_image_init->deleteImage($_GET["id"], $file_path);
        $blog_posts_init->deletePost($_GET["id"]);
    }

    function convertTitleToURL($str) { 
        $str = strtolower($str); 
        $str = preg_replace('/[^a-z0-9]+/', '-', $str); 
        $str = trim($str, '-'); 
        return $str;
    }
?>