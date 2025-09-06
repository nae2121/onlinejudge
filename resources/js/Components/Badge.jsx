// resources/js/Components/Badge.jsx
import React from "react";
const colors = {
  AC: "bg-green-100 text-green-800 ring-green-600/20",
  WA: "bg-red-100 text-red-800 ring-red-600/20",
  TLE: "bg-yellow-100 text-yellow-800 ring-yellow-600/20",
  RE: "bg-orange-100 text-orange-800 ring-orange-600/20",
  CE: "bg-gray-100 text-gray-800 ring-gray-600/20",
  PARTIAL: "bg-blue-100 text-blue-800 ring-blue-600/20",
  RUNNING: "bg-slate-100 text-slate-800 ring-slate-600/20",
  QUEUED: "bg-slate-100 text-slate-800 ring-slate-600/20",
};
export default function Badge({ text }) {
  const cls = colors[text] || "bg-slate-100 text-slate-800 ring-slate-600/20";
  return (
    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${cls}`}>
      {text}
    </span>
  );
}
