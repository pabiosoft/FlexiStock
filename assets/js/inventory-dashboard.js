import Chart from 'chart.js/auto';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from 'react-query';

const queryClient = new QueryClient();

export const initializeDashboard = () => {
    const dashboardRoot = document.getElementById('inventory-dashboard');
    if (!dashboardRoot) return;

    createRoot(dashboardRoot).render(
        <QueryClientProvider client={queryClient}>
            <DashboardApp />
        </QueryClientProvider>
    );
};

const DashboardApp = () => {
    const { data: stockStats } = useQuery('stockStats', async () => {
        const response = await fetch('/api/stock/statistics');
        return response.json();
    });

    useEffect(() => {
        if (!stockStats) return;

        const ctx = document.getElementById('stockChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stockStats.categories.map(cat => cat.name),
                datasets: [{
                    label: 'Stock Levels',
                    data: stockStats.categories.map(cat => cat.totalStock),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }, [stockStats]);

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            <StockSummaryCard stats={stockStats?.summary} />
            <LowStockAlerts items={stockStats?.lowStock} />
            <ExpiringItems items={stockStats?.expiringItems} />
            <canvas id="stockChart" className="col-span-full"></canvas>
        </div>
    );
};

const StockSummaryCard = ({ stats }) => {
    if (!stats) return null;

    return (
        <div className="bg-white p-4 rounded-lg shadow">
            <h3 className="text-lg font-semibold mb-2">Stock Summary</h3>
            <div className="grid grid-cols-2 gap-2">
                <div>
                    <p className="text-sm text-gray-600">Total Items</p>
                    <p className="text-xl font-bold">{stats.totalItems}</p>
                </div>
                <div>
                    <p className="text-sm text-gray-600">Total Value</p>
                    <p className="text-xl font-bold">${stats.totalValue.toFixed(2)}</p>
                </div>
            </div>
        </div>
    );
};

const LowStockAlerts = ({ items = [] }) => (
    <div className="bg-white p-4 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Low Stock Alerts</h3>
        <ul className="space-y-2">
            {items.map(item => (
                <li key={item.id} className="flex justify-between items-center">
                    <span>{item.name}</span>
                    <span className="text-red-500">{item.stockQuantity} left</span>
                </li>
            ))}
        </ul>
    </div>
);

const ExpiringItems = ({ items = [] }) => (
    <div className="bg-white p-4 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-2">Expiring Soon</h3>
        <ul className="space-y-2">
            {items.map(item => (
                <li key={item.id} className="flex justify-between items-center">
                    <span>{item.name}</span>
                    <span className="text-yellow-500">{item.daysUntilExpiry} days</span>
                </li>
            ))}
        </ul>
    </div>
);