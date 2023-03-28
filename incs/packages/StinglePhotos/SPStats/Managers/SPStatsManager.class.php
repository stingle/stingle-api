<?php

class SPStatsManager extends DbAccessor {

	const TBL_SP_STATS = "sp_stats";
	
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_USER = 1;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 1;
    
    protected $config;
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->config = $config;
	}
	
	public function addStat(SPStat $stat){

        $qb = new QueryBuilder();
		$insertArr = array(
			'new_users_today' => $stat->newUsersToday,
			'new_users_7days' => $stat->newUsers7Days,
			'new_users_31days' => $stat->newUsers31Days,
			'active_users_today' => $stat->activeUsersToday,
			'active_users_7days' => $stat->activeUsers7Days,
			'active_users_31days' => $stat->activeUsers31Days,
			'paid_users' => $stat->paidUsers,
			'mrr' => $stat->mrr,
			'total_users' => $stat->totalUsers
		);
		
        $qb->insert(Tbl::get("TBL_SP_STATS"))
            ->values($insertArr);

		return $this->query->exec($qb->getSQL())->affected();
	}
}
