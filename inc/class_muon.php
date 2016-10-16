<?php
Class Muon{
	protected $conn;
	protected $isbn;
	protected $ma_cuonsach;
	protected $tinhtrang;
	static $instance;
	function __construct(){
		global $conn;
		$this->conn = $conn;
		$this->table = 'cuonsach';

	}
	static function getInstance(){
        if(self::$instance !== null){
            return self::$instance;
        }
        self::$instance = new Muon();
        return self::$instance;
    }
    function muonSach($ma_cuonsach,$ma_docgia){
    	$checkdocgia 	= $this->kiemTraDocGia($ma_docgia);
    	$cuonsach 		= CuonSach::getInstance();
    	$isbn 			= $cuonsach->getISBNCuonSach($ma_cuonsach);
    	$checkdausach	= $this->kiemTraTinhTrangDauSach($ma_cuonsach,$isbn);

    	if( isCustomError($checkdocgia) ){
    		return $checkdocgia;
    	}
    	if( isCustomError( $checkdausach ) ){
    		return $checkdausach;
    	}
    	$ngay_muon 	= date('Y-m-d H:i:s', time() );
    	$ngay_hh 	= date("Y-m-d H:i:s", time()+ 14*24*60*60);
    	if($checkdocgia == 1 && $checkdausach == 1){
    		//INSERT INTO `muon` (`isbn`, `ma_cuonsach`, `ma_docgia`, `ngaygio_muon`, `ngay_hethan`) VALUES ('555', '55', '555', '2016-10-15 00:00:00', '2016-10-22 00:00:00');
    		$sql = "INSERT INTO `muon` (`isbn`, `ma_cuonsach`, `ma_docgia`, `ngaygio_muon`, `ngay_hethan`) VALUES ('{$isbn}', '{$ma_cuonsach}', '{$ma_docgia}', '{$ngay_muon}', '$ngay_hh');";
			if($this->conn->query($sql)){
				return $this->conn->insert_id;
			}
			return 0;
    	}
    }
    public static function list_books($select_all = 0, $posts_per_age = 10, $current_page = 1) {
		global $conn;
		if($select_all){
			$sql ="SELECT * FROM muon";
			$result = $conn->query($sql);

			if ($result && $result->num_rows > 0) {
				return $result;
			}
			return 0;
		} else {
			$offset = $posts_per_age * ($current_page - 1);
			$sql ="SELECT * FROM muon LIMIT {$posts_per_age} OFFSET {$offset}";

			$result = $conn->query($sql);
			if ($result && $result->num_rows > 0) {
				return $result;
			}
			return 0;
		}
	}
    /**
     * kiểm tra xem Độc giả này có được phép mượn sách hay không.
     * @version  1.0
     * @author danng
     * @return  boolean true or false
     */
    function kiemTraDocGia( $ma_docgia ){
    	$docgia 	= DocGia::getInstance()->getThongTinDocGia($ma_docgia);
    	if(!$docgia){
    		return new HandleError('docgia','no_exist');
    	}
    	$docgia 	= TreEm::getInstance();

		$is_tre_em =  $docgia->isDocGiaTreEm($ma_docgia) ;
		if( $is_tre_em ) {
			$ma_docgia = $docgia->maDocGiaNguoiLon($ma_docgia);
			$docgia 	= $docgia->getThongTinDocGia($ma_docgia);
		} else {
			$nguoilon 	= NguoiLon::getInstance();
			$docgia 	= $nguoilon->getThongTinDocGia($ma_docgia);
	    	if($docgia['con_hsd'] == 0)
	    		return new HandleError('docgia','hethan');
    	}
    	if($docgia['so_sachdangmuon'] > 5)
	    	return new HandleError('docgia','quasoluong');
    	return 1;
    }
    /**
     * kiểm tra xem đầu sách này còn cuốn sách nào trong thư việc không
     * Nếu còn --> trả về ID 1 cuốn sách của đầ sách này;
     * Nếu không -> trả về lỗi;
     *@return   [<description>] bool : true or false
     */
    function kiemTraTinhTrangDauSach($ma_cuonsach, $isbn) {
    	$cuonsach = CuonSach::getInstance()->kiemTraSachCuonSach($ma_cuonsach);
    	if( !$cuonsach ){
    		return new HandleError('sach','no_exists');
    	}
    	$dausach = DauSach::getInstance()->kiemTraTrangThaiDauDachByISBN($isbn);
    	if( !$dausach ){
    		return new HandleError('dausach','busy');
    	}
    	return true;
    }
    /**
     * nếu đầu sách còn có trong thư viện. Lấy một mã cuốn sách của đầu sách này  và cho người dùng mượn.
     * @version [version]
     * @since   1.0
     * @author danng
     * @param   [type]    $isbn [description]
     * @return  [type]          [description]
     */
    function chonCuonSach($isbn){

    }

	public static function listBookByISBN($select_all = 0,$isbn = 0, $posts_per_age = 10, $current_page = 1) {
		global $conn;
		if($select_all){
			$sql ="SELECT * FROM cuonsach WHERE isbn = '{$isbn}' ";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				return $result;
			}
			return 0;
		} else {
			$offset = $posts_per_age * ($current_page - 1);
			$sql ="SELECT * FROM cuonsach WHERE isbn='{$isbn}' LIMIT {$posts_per_age} OFFSET {$offset}";

			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				return $result;
			}
			return 0;
		}
	}

	function check_tua_sach($ma_tuasach){
		$sql ="SELECT * FROM {$this->table} WHERE ma_tuasach = {$ma_tuasach} ";
		$result = $this->conn->query($sql);
		if ($result->num_rows > 0) {
			return $result;
		}
		return 0;
	}

}