import { useEffect, useState } from 'react';
import axios from 'axios';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip,
  PieChart, Pie, Cell, ResponsiveContainer, Legend,
} from 'recharts';

interface AnalyticsData {
  link: { original_url: string; short_code: string };
  total_clicks: number;
  clicks_per_day: { date: string; total: number }[];
  device_breakdown: { device_type: string; total: number }[];
  browser_breakdown: { browser: string; total: number }[];
}

const COLORS = ['#3b82f6', '#f97316', '#22c55e', '#a855f7'];

export default function AnalyticsDashboard({ linkId }: { linkId: number }) {
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    axios.get(`http://127.0.0.1:8000/api/links/${linkId}/analytics`)
      .then((res) => setData(res.data))
      .catch(() => setError('Gagal ambil data analytics'))
      .finally(() => setLoading(false));
  }, [linkId]);

  if (loading) return <p>Loading...</p>;
  if (error) return <p className="text-red-500">{error}</p>;
  if (!data) return null;

  return (
    <div className="p-6 space-y-8">
      <div>
        <h1 className="text-2xl font-bold">{data.link.original_url}</h1>
        <p className="text-gray-500">Total klik: {data.total_clicks}</p>
      </div>

      <div>
        <h2 className="text-lg font-semibold mb-2">Klik per Hari</h2>
        <ResponsiveContainer width="100%" height={250}>
          <LineChart data={data.clicks_per_day}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis allowDecimals={false} />
            <Tooltip />
            <Line type="monotone" dataKey="total" stroke="#3b82f6" />
          </LineChart>
        </ResponsiveContainer>
      </div>

      <div className="grid grid-cols-2 gap-6">
        <div>
          <h2 className="text-lg font-semibold mb-2">Device</h2>
          <ResponsiveContainer width="100%" height={200}>
            <PieChart>
              <Pie
                data={data.device_breakdown}
                dataKey="total"
                nameKey="device_type"
                outerRadius={70}
                label
              >
                {data.device_breakdown.map((_, i) => (
                  <Cell key={i} fill={COLORS[i % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
              <Legend />
            </PieChart>
          </ResponsiveContainer>
        </div>

        <div>
          <h2 className="text-lg font-semibold mb-2">Browser</h2>
          <ResponsiveContainer width="100%" height={200}>
            <PieChart>
              <Pie
                data={data.browser_breakdown}
                dataKey="total"
                nameKey="browser"
                outerRadius={70}
                label
              >
                {data.browser_breakdown.map((_, i) => (
                  <Cell key={i} fill={COLORS[i % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
              <Legend />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  );
}