
// resources/js/Pages/Problems/Index.jsx
import React from "react";
import { Head, Link } from "@inertiajs/react";

export default function Index({ problems }) {
  return (
    <div className="min-h-screen bg-gray-50">
      <Head title="Problems" />
      <header className="bg-white shadow">
        <div className="mx-auto max-w-6xl px-6 py-6">
          <h1 className="text-xl font-semibold">AtCoder-ish OJ</h1>
          <p className="text-sm text-gray-500">Problems</p>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-6 py-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {problems.map((p) => (
            <Link key={p.slug} href={`/problems/${p.slug}`} className="block">
              <div className="rounded-2xl bg-white shadow-sm hover:shadow-md transition p-5">
                <div className="flex items-center justify-between">
                  <h2 className="text-base font-semibold">{p.title}</h2>
                  <span className="text-xs text-gray-500">{p.slug}</span>
                </div>
                <div className="mt-3 text-sm text-gray-600 space-y-1">
                  <p>Time: {p.time_limit_ms} ms</p>
                  <p>Memory: {p.memory_limit_mb} MB</p>
                </div>
                <div className="mt-3 flex flex-wrap gap-2">
                  {(p.allowed_langs?.langs || p.allowed_langs || ["cpp","python"]).map((l) => (
                    <span key={l} className="text-xs rounded bg-slate-100 px-2 py-0.5">{l}</span>
                  ))}
                </div>
              </div>
            </Link>
          ))}
        </div>
      </main>
    </div>
  );
}
