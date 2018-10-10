<?php
/***
 * Bu class'tan bir adet obje oluşturulur, $obj = new Test();
 * $obj->getPageResultsFacade($page=1)  methodu parametre olarak $page ' i alır ve
 * 
 * 
 * Return olarak aşağıdaki gibi bir JSON objesi return eder:
 * 	 {
 *   "0":{10 posts},
 *	 "2":{4 users},
 *	 "3":{10 posts},
 *	 "4":{1 ad},
 *	 "5":{10 posts},
 *	 "6":{1 survey},
 *	  } (total 36 items)
 * 
 ***/
class Test{	

	/*
	db_settings.ini:
	[database]
	driver = mysql
	host = localhost
	;port = 3306
	schema = db_schema
	username = user
	password = secret
	*/	
	protected $pdo=null;
	
	/***
 	* Params:
	* $file : File type, for databse connection settings	
	*
 	***/
	public function __construct($settings){		       
        	$dns = $settings['driver'] .
        	':host=' . $settings['host'] .
       		((!empty($settings['port'])) ? (';port=' . $settings['port']) : '') .
			';dbname=' . $settings['schema'];		
		try {
			$this->pdo = new \PDO($dns, $settings['username'], $settings['password']);
			//PDO objemize ozelliklerimizi atiyoruz, hata modunu aciyoruz
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			}catch(Exception $e){
			die("Unable to connect: " . $e->getMessage());
		}		
	}
	
	
	/***
	 *  Bu fonksiyon disariya sonuclari veriyor
	 * 	10 post 4 user 10 post 1 ad 10 post 1 survey , bu sirayla 36 itemi JSON olarak return eder
	 * 	Return JSON:
	 * 	
	 * {
	 * 	 "0":{10 posts},	
	 * 	 "2":{4 users},
	 * 	 "3":{10 posts},
	 * 	 "4":{1 ad},
	 * 	 "5":{10 posts},
	 * 	 "6":{1 survey},
	 * 	}
	 * 
	 * Sonsuz seklinde return yapar, butun tabloardaki rowlar biterse en bastan 1'den baslayarak gostermeye devam eder.
	 * Ornegin Post tablosun 3000 satir var, Survey tablosunde 20 satir var.
	 * Maksimum sayfa 100 olabilir cunku 3000 post var, fakat 20.sayfadan sonra 21.sayfada ilk survey tekrar goruntulenir,
	 * 24.sayfada 4.survey goruntulenir, tabloda az elemanı bulunanlar, sayfa sayisi kendi maksimum sayfa sayisini gecerse , tekrar bastan saymaya baslarlar.
	 * 	
	***/
	function getPaginationResults($page=1){
			try { 
				$page = 1;
				if(!empty($_GET['page'])) {
					$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
				if(false === $page) {
					$page = 1;
				}
				}
				
				//Counts of table rows
				$posts_count = $this->pdo->query('SELECT COUNT(id) FROM Post', PDO::FETCH_COLUMN);
				$users_count = $this->pdo->query('SELECT COUNT(id) FROM User', PDO::FETCH_COLUMN);
				$ads_count = $this->pdo->query('SELECT COUNT(id) FROM Advertisement', PDO::FETCH_COLUMN);
				$surveys_count = $this->pdo->query('SELECT COUNT(id) FROM Survey', PDO::FETCH_COLUMN);
								
				// set the number of items to display per page
				$posts_per_page = 30;
				$users_per_page = 4;
				$ads_per_page = 1;
				$surveys_per_page = 1;
				
				/**
				 * Page numbers of tables 
				**/
				$posts_pages = ceil($posts_count / $posts_per_page );
				$users_pages = ceil($users_count / $users_per_page );
				$ads_pages = ceil($ads_count / $ads_per_page );
				$surveys_pages = ceil($survyes_count / $surveys_per_page );				
				
				//Maximum numbers of page
				$max_pages = max($posts_pages, $users_pages, $ads_pages, $surveys_pages);
				
				if($page > $max_pages){
					$page = 1;
				}
				
				/*
				For infinitive loop , we find the remainder of pages
				*/				
				if($page > $posts_pages ){
					$posts_page = $page - $posts_pages*floor($page / $posts_pages);
				}				
				if($page > $users_pages ){
					$users_page = $page - $users_pages*floor($page / $users_pages);
				}				
				if($page > $ads_pages ){
					$ads_page = $page - $ads_pages*floor($page / $ads_pages );
				}				
				if($page > $surveys_pages ){
					$surveys_page = $page - $surveys_pages*floor($page / $surveys_pages);
				}

				// calculating offsets
				$offset_posts = ($posts_page-1)*$posts_per_page;
				$offset_users = ($users_page-1)*$users_per_page;
				$offset_ads = ($ads_page-1)*$ads_per_page;
				$offset_surveys = ($surveys_page-1)*$surveys_per_page;				
						
				// Asagida kullanilan find* foksiyonlari $limit ve $offset parametlerini sirasiyla alir ve bu degelere gore db'den sorgu sonucu FETCH_ASSOC ile array seklinde return ederler
				//son 30 postu aliyoruz
				$posts = $this.findApprovedPosts($posts_per_page, $offset_posts );
				
				$first10posts = array_slice($posts,0, 10);
				$second10posts = array_slice($posts,10, 10);
				$third10posts = array_slice($posts,20, 10);
				
				//son 4 yeni useri aliyoruz
				$users = $this.findLastUsers($users_per_page, $offset_users);
				
				//en yeni 1 tane advertisementi aliyoruz
				$ads = $this.findCurrentAds($ads_per_page , $offset_ads);
				
				//en yeni 1 tane survey ve cevaplarini array icinde aliyoruz
				$surveys = $this.findSurveysAndAnswers($surveys_per_page , $offset_surveys);	
				
				$pageResults = [$first10posts, $users, $second10posts, $ads, $third10posts, $surveys];

				return json_encode($pageResults, JSON_FORCE_OBJECT);
			
			} catch(Exception $e){
				echo "Failed: " . $e->getMessage();
		    }			

	}
	
	/***
	Params:
	$limit : int type
	$offset : int type

	Return: Resulsts of approvded posts query as array 	
	***/	
	function findApprovedPosts($limit, $offset){
		try{ 
		    $sql = 'SELECT id,title,image,content,video_url,video_file,type FROM Post 
			WHERE approved= :approved 
			ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
			$query = $this->pdo->prepare($sql);
			$query->bindParam(':limit', $limit, PDO::PARAM_INT); 
			$query->bindParam(':approved', 1, PDO::PARAM_INT); 
			$query->bindParam(':offset', $offset, PDO::PARAM_INT); 
			$query->execute();
			return $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			throw $e;
		}
	}
	
	/***
	Params:
	$limit : int type
	$offset : int type

	Return resulsts of approvded posts query  as array 
	***/	
	function findLastUsers($limit, $offset){
		try{ 
			$sql = 'SELECT id,name,surname,email,phone,title,company FROM Users 
			WHERE status= :status 
			ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
			
			$query = $this->pdo->prepare($sql);
			$query->bindParam(':status', 'ACCEPTED', PDO::PARAM_STR); 
			$query->bindParam(':limit', $limit, PDO::PARAM_INT); 
			$query->bindParam(':offset', $offset, PDO::PARAM_INT); 
			$query->execute();
			return $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			throw $e;
		}
	}
	
	/***
	Params:
	$limit : int type
	$offset : int type

	Return resulsts of users who born at current day 
	***/
	function findUsersWhoBornToday($limit, $offset){
		try{ 
			$sql = "SELECT id,name,surname,email,phone,title,company FROM Users 
			WHERE status= :status AND DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(:currentMonthDay, '%m-%d')
			ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
			
			$query = $this->pdo->prepare($sql);
			$query->bindParam(':status', 'ACCEPTED', PDO::PARAM_STR);
			$currentDate = new DateTime('NOW');
			$currentMonthDay = $currentDate->format('Y-m-d');
			
			$query->bindParam(':currentMonthDay', $currentMonthDay, PDO::PARAM_STR); 
			$query->bindParam(':status', 'ACCEPTED', PDO::PARAM_STR); 
			$query->bindParam(':limit', $limit, PDO::PARAM_INT); 
			$query->bindParam(':offset', $offset, PDO::PARAM_INT); 
			$query->execute();
			return $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			throw $e;
			echo $e;
		}
	}
	
	
	/***
	Params:
	$limit : int type
	$offset : int type

	Return resulsts of matching ads 
	***/
	function findCurrentAds($limit, $offset){
		try{ 
			$sql = 'SELECT id,image,image_url,video,video_url,ad_url,title FROM Advertisement 
			WHERE (start_date < :date AND :date < end_date) 
			ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
			$query = $this->pdo->prepare($sql);
			$currentDate = new DateTime('NOW');
			$currentFormattedDate = $currentDate->format('Y-m-d');
			$query->bindParam(':date', $currentFormattedDate, PDO::PARAM_STR); 
			$query->bindParam(':limit', $limit, PDO::PARAM_INT); 
			$query->bindParam(':offset', $offset-1, PDO::PARAM_INT);
			$query->execute();
			return $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			throw $e;
		}
	}
	
	/***
	Params:
	$limit : int type
	$offset : int type

	Return resulsts of approved surveys and its answers 
	***/
	function findSurveysAndAnswers($limit, $offset){
		try{ 
		
			$sql = 'SELECT id,question,user_id
			FROM Survey S WHERE approved = :approved
			ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
			
			$query = $this->pdo->prepare($sql);
			$query->bindParam(':approved', 1 , PDO::PARAM_INT); 
			$query->bindParam(':limit', $limit, PDO::PARAM_INT); 
			$query->bindParam(':offset', $offset-1, PDO::PARAM_INT); 
			$surveys = $query->fetchAll(PDO::FETCH_ASSOC);

			$len = count($surveys);
			$answers[] = array();
			array_push();
			for($i=0; $len; $i++){
				$survey_id = $surveys[$i]['id'];
				$query = $this->pdo->prepare('SELECT SA.answer,SA.answer_count, question_id 
				FROM SurveyAnswers SA WHERE question_id = :question_id ORDER BY answer_order DESC');
				$query->bindParam(':approved', 1 , PDO::PARAM_STR);
				$query->bindParam(':question_id', $survey_id , PDO::PARAM_STR);
				$query->execute();
				
				$surveys[$i]['answers'] = $query->fetchAll(PDO::FETCH_ASSOC);
			}

						
			return array($surveys);		
			
		}catch(Exception $e){
			throw $e;
		}
	}

	/***
	Params:
	$limit : int type
	$offset : int type

	Return resulsts of matching ads 
	***/
	function listUserByLocationUpdated(){
		try{ 
			$sql = "SELECT U.id, U.name, U.surname, U.email, L.location_address, L.location_latitude, L.location_longitude, L.updated_at
			FROM Users U
			INNER JOIN Locations L ON U.id = L.user_id
			WHERE U.status = :status
			ORDER BY L.updated_at DESC";

			$query = $this->pdo->prepare($sql);
			$status='ACCEPTED';			
			$query->bindParam(':status', $status, PDO::PARAM_STR); 
			$query->execute();
			return $query->fetchAll(PDO::FETCH_ASSOC);
			
		}catch(Exception $e){
			throw $e;
		}
	}	
	}	