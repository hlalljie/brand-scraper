import { React, useState, use } from "react";

const Home = () => {
    const [input, setInput] = useState("");
    const [resData, setResData]: [Object | null, any] = useState(null);

    const handleSubmit = () => {
        fetch("/api/brand_scraper", {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(data);
            });
    };

    return (
        <>
            <div>
                <h1>Home</h1>
            </div>
            <input
                type="text"
                onChange={(e) => setInput(e.target.value)}
            ></input>
            <button onClick={handleSubmit}>Submit</button>
            {resData && (
                <div className="results">
                    <h2>Res Data</h2>
                    <p>{JSON.stringify(resData)}</p>
                </div>
            )}
        </>
    );
};

export default Home;
