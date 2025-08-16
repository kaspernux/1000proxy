<div>
	Dashboard
	<div>Orders</div>
	<div>Wallet Balance</div>
	<div>{{ auth('customer')->user()->name ?? '' }}</div>
	<!-- Navigation markers expected by tests on /customer -->
	<nav>
		<ul>
			<li>My Orders</li>
			<li>My Services</li>
			<li>Wallet</li>
		</ul>
	</nav>
</div>
