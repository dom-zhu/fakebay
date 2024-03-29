<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Product_model extends CI_Model
{

	public function __construct()
	{
		$this->load->database();
	}

	// creates a new listing for a product and returns the new product_ID
	public function createListing($name, $desc, $price, $filenames, $seller_id, $end_datetime) {
		// add new product
		$query = "insert into products (product_name, description, current_price, seller_id, end_datetime) values (?, ?, ?, ?, ?)";
		$this->db->query($query, array($name, $desc, $price, $seller_id, $end_datetime));

		// get product id
		$idquery = "select product_id from products order by product_id desc limit 1";
		$product_id = $this->db->query($idquery)->row()->product_id;
		// var_dump($filenames);
		// add to photos
		foreach ($filenames as $filename) {
			$photo_query = "insert into photos (product_id, filename) values (?, ?)";
			$this->db->query($photo_query, array($product_id, $filename));
		}

		return $product_id;
	}

	// retrieves the bidding history for an item
	public function getBidHistory($product_id) {
		$query = sprintf('select * from product_bids where product_ID = ? order by bid_price desc limit 5');
		$results = $this->db->query($query, array($product_id));
		return $results;
	}

	// searches for products which have closely matching titles
	public function search($searchterm) {
		$searchterm = strtolower($searchterm);
		$all_results = $this->db->query("select * from products");
		$array = array();
		// somewhat fuzzy search
		foreach ($all_results->result() as $row) {
			similar_text(strtolower($row->product_name), $searchterm, $perc);
			if ($perc > 30) {
				$n = 1;
				$metone = metaphone($searchterm);
				$mettwo = metaphone($row->product_name);
				$le = levenshtein($metone, $mettwo);
        		$n = 100 * (strlen($mettwo) - $le) / strlen($mettwo);
				if ($n > 20) {
					$perc += $n;
					$array[$row->product_id] = array($row, $perc);
				}
			} else if (strlen($searchterm) == 0) {
				$array[$row->product_id] = array($row, $perc);
			}
		}
	
		usort($array, function ($a, $b) {
			return $b[1] - $a[1];
		});
		
		return $array;
	}

	// Adds given product to wishlist 
	public function addToWishlist($user_id, $product_id) {
		if (!$this->product_model->inWishlist($user_id, $product_id)) {
			$query = "insert into wishlist (user_id, product_id) values (?, ?)";
			$result = $this->db->query($query, array($user_id, $product_id));
		}
	}

	// deletes item from wishlist
	public function deleteFromWishlist($user_id, $product_id) {
		$query = "delete from wishlist where user_id = ? and product_id = ?";
		$result = $this->db->query($query, array($user_id, $product_id));
	}

	// retrieves the wishlist for the user
	public function retrieveWishlist($user_id) {
		$query = "select * from wishlist, products where wishlist.product_id = products.product_id and wishlist.user_id = ?";
		$result = $this->db->query($query, array($user_id));
		
		return $result;
	}

	// if product in wishlist return 1 else return 0
	public function inWishlist($user_id, $product_id) {
		$query = "select * from wishlist where user_id = ? and product_id = ?";
		$result = $this->db->query($query, array($user_id, $product_id));
		
		// echo $result->num_rows();
		if ($result->num_rows() == 0) {
			return 0;
		}
		return 1;
	}

	// retrieves the time in seconds until the auction has ended
	public function auctionEndSeconds($product_id) {
		$query = "select (to_seconds(end_datetime) - to_seconds(now())) as 'sec' from products 
		where product_id = ?";
		$result = $this->db->query($query, array($product_id));

		return $result->row();
	}

	// retrieves the details for a product given the product_id
	public function getDetails($product_id) {
		$query = sprintf('select * from products where product_id = ?');
		$result = $this->db->query($query, array($product_id))->row();
		return $result;
	}

	// retrieves the photos for a listing
	public function getPhotos($product_id) {
		$query = "select * from photos where product_id = ?";
		$result = $this->db->query($query, array($product_id));
		return $result;
	}

	// retrieves the details for the seller given their seller_Id
	public function getSellerDetails($seller_id) {
		$query = sprintf('select distinct(user_id), username, seller_rating from users, products where users.user_id = 
							products.seller_id and products.seller_id = %s', $seller_id);
		$result = $this->db->query($query)->row();
		return $result;
	}

	// gets the current price of the product
	public function getCurrentPrice($product_id) {
		$query = "select current_price from products where product_id = {$product_id}";
		$result = $this->db->query($query)->row();
		return $result->current_price;
	}


	// submits a bid for a product
	public function submitBid($product_Id, $bid_price, $user_ID) {
		// need to update product product_bids first
		$bid_query = "insert into product_bids (product_ID, user_ID, bid_price) values (?, ?, ?)";

		// need to update product table to reflect current price
		$product_query = "update products set current_price = ? where product_id = ?";

		$this->db->query($bid_query, array($product_Id, $user_ID, $bid_price));
		$this->db->query($product_query, array($bid_price, $product_Id));
	}

}