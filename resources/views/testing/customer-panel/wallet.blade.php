<div>
	Wallet
	<div>Transactions</div>
	<div>{{ optional(auth('customer')->user()->wallet)->balance }}</div>
</div>
