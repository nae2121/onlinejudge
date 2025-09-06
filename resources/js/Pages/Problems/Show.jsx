// resources/js/Pages/Problems/Show.jsx
import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import Badge from "../../Components/Badge";

export default function Show({ problem }) {
  const allowed = problem.allowed_langs?.langs || problem.allowed_langs || ["cpp","python"];
  const [lang, setLang] = useState(allowed[0]);
  const [code, setCode] = useState("// write your solution here\n");
  const [submissionId, setSubmissionId] = useState(null);
  const [latest, setLatest] = useState(null);

  const submit = async () => {
    const res = await fetch(`/problems/${problem.slug}/submit`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        "Accept": "application/json"
      },
      body: JSON.stringify({ lang, code }),
    });
    if (res.ok) {
      const j = await res.json();
      setSubmissionId(j.submission_id);
    }
  };

  const fetchStatus = async () => {
    if (!submissionId) return;
    const res = await fetch(`/api/submissions/${submissionId}`);
    if (res.ok) setLatest(await res.json());
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Head title={problem.title} />
      <header className="bg-white shadow">
        <div className="mx-auto max-w-6xl px-6 py-6">
          <h1 className="text-xl font-semibold">{problem.title}</h1>
          <p className="text-sm text-gray-500">{problem.slug}</p>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-8 space-y-8">
        <section className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-base font-semibold">Constraints</h2>
          <div className="mt-3 text-sm text-gray-700 grid grid-cols-2 sm:grid-cols-4 gap-y-2">
            <div>Time: <span className="font-medium">{problem.time_limit_ms} ms</span></div>
            <div>Memory: <span className="font-medium">{problem.memory_limit_mb} MB</span></div>
            <div className="col-span-2">
              Langs: {allowed.map(l => <span key={l} className="text-xs rounded bg-slate-100 px-2 py-0.5 mr-1">{l}</span>)}
            </div>
          </div>
          {problem.scoring?.type === "sum_subtasks" && (
            <div className="mt-3 text-sm text-gray-600">
              <span className="font-medium">Subtasks:</span>{" "}
              {Object.entries(problem.scoring.groups).map(([g,p]) => (
                <span key={g} className="mr-2">{g}: {p}pt</span>
              ))}
            </div>
          )}
        </section>

        <section className="rounded-2xl bg-white p-6 shadow-sm">
          <h2 className="text-base font-semibold">Submit</h2>
          <div className="mt-3 flex flex-col gap-3">
            <div className="flex items-center gap-3">
              <label className="text-sm text-gray-600">Language</label>
              <select value={lang} onChange={e=>setLang(e.target.value)} className="rounded-lg border-gray-300 text-sm">
                {allowed.map(l => <option key={l} value={l}>{l}</option>)}
              </select>
            </div>
            <textarea value={code} onChange={e=>setCode(e.target.value)} className="w-full h-56 rounded-xl border-gray-300 font-mono text-sm p-3" />
            <div className="flex gap-3">
              <button onClick={submit} className="px-4 py-2 rounded-xl bg-black text-white text-sm hover:opacity-90">提出する</button>
              {submissionId && (
                <button onClick={fetchStatus} className="px-4 py-2 rounded-xl bg-slate-800 text-white text-sm hover:opacity-90">採点状況を見る</button>
              )}
            </div>
          </div>

          {latest && (
            <div className="mt-6 rounded-xl border p-4">
              <div className="flex items-center justify-between">
                <div className="text-sm text-gray-600">Submission #{latest.id}</div>
                <Badge text={latest.status} />
              </div>
              <div className="mt-2 text-sm text-gray-700">Points: <span className="font-semibold">{latest.points}</span></div>
              <div className="mt-2">
                <details className="text-sm">
                  <summary className="cursor-pointer">詳細</summary>
                  <pre className="mt-2 whitespace-pre-wrap text-xs bg-slate-50 p-2 rounded">{JSON.stringify(latest.detail, null, 2)}</pre>
                </details>
              </div>
            </div>
          )}
        </section>
      </main>
    </div>
  );
}
