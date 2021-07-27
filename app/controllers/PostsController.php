<?php
class PostsController extends \devpirates\MVC\Base\Controller {
    public function index() {
        header("location: /404/notfound/");
    }

    public function Post($postId, $postName) {
        $postHelper = new PostHelper();
        $post = $postHelper->GetPost($postId);
        $this->view($post);
    }
}
?>